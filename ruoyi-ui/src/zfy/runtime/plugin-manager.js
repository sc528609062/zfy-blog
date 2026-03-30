import { zfyConfigCenter, safeJsonParse } from './config-center'

const PLUGIN_STATE_KEY = 'zfy_plugin_state'

function normalizePluginSettings(plugin, currentSettings = {}) {
  const settings = {}
  const schema = plugin.settings || []
  schema.forEach(field => {
    if (currentSettings[field.key] !== undefined) {
      settings[field.key] = currentSettings[field.key]
    } else {
      settings[field.key] = field.default
    }
  })
  return settings
}

class ZfyPluginManager {
  constructor() {
    this.pluginMap = new Map()
    this.state = {
      enabledMap: {},
      settingsMap: {}
    }
    this.listeners = []
    this.initialized = false
  }

  registerPlugin(plugin) {
    if (!plugin || !plugin.id) {
      return
    }
    this.pluginMap.set(plugin.id, plugin)
    if (this.state.enabledMap[plugin.id] === undefined) {
      this.state.enabledMap[plugin.id] = plugin.defaultEnabled !== false
    }
    this.state.settingsMap[plugin.id] = normalizePluginSettings(plugin, this.state.settingsMap[plugin.id] || {})
  }

  registerPlugins(pluginList = []) {
    pluginList.forEach(plugin => this.registerPlugin(plugin))
  }

  getPluginList() {
    return Array.from(this.pluginMap.values()).map(plugin => ({
      ...plugin,
      enabled: this.isEnabled(plugin.id),
      runtimeSettings: this.getSettings(plugin.id)
    }))
  }

  isEnabled(pluginId) {
    return this.state.enabledMap[pluginId] !== false
  }

  getSettings(pluginId) {
    const plugin = this.pluginMap.get(pluginId)
    if (!plugin) {
      return {}
    }
    return normalizePluginSettings(plugin, this.state.settingsMap[pluginId] || {})
  }

  notify() {
    const payload = {
      plugins: this.getPluginList(),
      state: this.state
    }
    this.listeners.forEach(listener => {
      listener(payload)
    })
  }

  onChange(listener) {
    if (typeof listener === 'function') {
      this.listeners.push(listener)
    }
    return () => {
      this.listeners = this.listeners.filter(item => item !== listener)
    }
  }

  async initialize() {
    if (this.initialized) {
      return
    }
    const rawState = await zfyConfigCenter.getValue(PLUGIN_STATE_KEY, '{}')
    const parsedState = safeJsonParse(rawState || '{}', {})
    this.state = {
      enabledMap: parsedState.enabledMap || {},
      settingsMap: parsedState.settingsMap || {}
    }

    this.pluginMap.forEach(plugin => {
      if (this.state.enabledMap[plugin.id] === undefined) {
        this.state.enabledMap[plugin.id] = plugin.defaultEnabled !== false
      }
      this.state.settingsMap[plugin.id] = normalizePluginSettings(plugin, this.state.settingsMap[plugin.id] || {})
    })
    this.initialized = true
    this.notify()
  }

  async persist() {
    await zfyConfigCenter.save(PLUGIN_STATE_KEY, this.state, {
      group: 'plugin',
      desc: 'ZFY插件运行状态'
    })
  }

  async setEnabled(pluginId, enabled) {
    if (!this.pluginMap.has(pluginId)) {
      return
    }
    this.state = {
      ...this.state,
      enabledMap: {
        ...this.state.enabledMap,
        [pluginId]: !!enabled
      }
    }
    this.notify()
    await this.persist()
  }

  async updateSetting(pluginId, fieldKey, value) {
    const plugin = this.pluginMap.get(pluginId)
    if (!plugin) {
      return
    }
    const oldSettings = this.getSettings(pluginId)
    this.state = {
      ...this.state,
      settingsMap: {
        ...this.state.settingsMap,
        [pluginId]: {
          ...oldSettings,
          [fieldKey]: value
        }
      }
    }
    this.notify()
    await this.persist()
  }

  applyFilters(hookName, payload, context = {}) {
    let value = payload
    this.pluginMap.forEach(plugin => {
      if (!this.isEnabled(plugin.id)) {
        return
      }
      const hooks = plugin.hooks || {}
      const filters = hooks.filters || {}
      const filterHandler = filters[hookName]
      if (typeof filterHandler === 'function') {
        value = filterHandler(value, context, this.getSettings(plugin.id))
      }
    })
    return value
  }

  runActions(hookName, payload, context = {}) {
    this.pluginMap.forEach(plugin => {
      if (!this.isEnabled(plugin.id)) {
        return
      }
      const hooks = plugin.hooks || {}
      const actions = hooks.actions || {}
      const actionHandler = actions[hookName]
      if (typeof actionHandler === 'function') {
        actionHandler(payload, context, this.getSettings(plugin.id))
      }
    })
  }

  resolveWidgets(area, context = {}) {
    const list = []
    this.pluginMap.forEach(plugin => {
      if (!this.isEnabled(plugin.id)) {
        return
      }
      const widgets = plugin.widgets || []
      widgets.forEach(widget => {
        if (widget.area !== area) {
          return
        }
        if (typeof widget.when === 'function' && !widget.when(context, this.getSettings(plugin.id))) {
          return
        }
        const rendered = typeof widget.render === 'function'
          ? widget.render(context, this.getSettings(plugin.id))
          : widget.render
        if (rendered) {
          list.push({
            pluginId: plugin.id,
            widgetId: widget.id,
            order: widget.order || 0,
            ...rendered
          })
        }
      })
    })
    return list.sort((a, b) => a.order - b.order)
  }
}

export const zfyPluginManager = new ZfyPluginManager()
