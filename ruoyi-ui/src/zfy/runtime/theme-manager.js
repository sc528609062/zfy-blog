import { zfyConfigCenter, safeJsonParse } from './config-center'

const THEME_ACTIVE_KEY = 'zfy_theme_active'
const THEME_SETTINGS_KEY = 'zfy_theme_settings'

function buildFocusShadow(color) {
  if (!color || color[0] !== '#' || (color.length !== 7 && color.length !== 4)) {
    return 'rgba(253, 83, 161, 0.35)'
  }
  if (color.length === 4) {
    const r = color[1] + color[1]
    const g = color[2] + color[2]
    const b = color[3] + color[3]
    return `rgba(${parseInt(r, 16)}, ${parseInt(g, 16)}, ${parseInt(b, 16)}, 0.35)`
  }
  const r = color.slice(1, 3)
  const g = color.slice(3, 5)
  const b = color.slice(5, 7)
  return `rgba(${parseInt(r, 16)}, ${parseInt(g, 16)}, ${parseInt(b, 16)}, 0.35)`
}

class ZfyThemeManager {
  constructor() {
    this.themeMap = new Map()
    this.activeThemeId = ''
    this.themeSettings = {}
    this.listeners = []
    this.initialized = false
  }

  registerTheme(theme) {
    if (!theme || !theme.id) {
      return
    }
    this.themeMap.set(theme.id, theme)
    if (!this.activeThemeId) {
      this.activeThemeId = theme.id
    }
  }

  registerThemes(themes = []) {
    themes.forEach(theme => this.registerTheme(theme))
  }

  getThemeList() {
    return Array.from(this.themeMap.values())
  }

  getThemeById(themeId) {
    return this.themeMap.get(themeId)
  }

  getActiveTheme() {
    return this.getThemeById(this.activeThemeId) || this.getThemeList()[0]
  }

  getSettingsForTheme(themeId = this.activeThemeId) {
    const theme = this.getThemeById(themeId)
    if (!theme) {
      return {}
    }
    const custom = this.themeSettings[themeId] || {}
    const tokens = theme.tokens || {}
    return {
      ...tokens,
      ...custom,
      focusShadowColor: buildFocusShadow(custom.accentColor || tokens.accentColor)
    }
  }

  getCssVariables() {
    const settings = this.getSettingsForTheme()
    return {
      '--zfy-theme-color': settings.accentColor,
      '--zfy-main-max-width': `${settings.mainMaxWidth}px`,
      '--zfy-main-radius': `${settings.cardRadius}px`,
      '--zfy-body-bg-color': settings.pageBackground,
      '--zfy-main-bg-color': settings.contentBackground,
      '--zfy-key-color': settings.textPrimary,
      '--zfy-main-color': settings.textSecondary,
      '--zfy-muted-color': settings.textMuted,
      '--zfy-main-border-color': settings.borderColor,
      '--zfy-main-shadow-color': settings.shadowColor,
      '--zfy-focus-shadow-color': settings.focusShadowColor,
      '--zfy-header-bg': settings.enableBlurHeader ? 'rgba(255, 255, 255, 0.78)' : settings.contentBackground
    }
  }

  applyToDocument() {
    if (typeof document === 'undefined') {
      return
    }
    const root = document.documentElement
    const cssVariables = this.getCssVariables()
    Object.keys(cssVariables).forEach(key => {
      root.style.setProperty(key, cssVariables[key])
    })
    const currentTheme = this.getActiveTheme()
    if (currentTheme) {
      root.setAttribute('data-zfy-theme', currentTheme.id)
    }
    document.body.classList.add('zfy-theme-mounted')
  }

  notify() {
    const payload = {
      activeThemeId: this.activeThemeId,
      activeTheme: this.getActiveTheme(),
      settings: this.getSettingsForTheme()
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
    const [activeThemeValue, themeSettingsValue] = await Promise.all([
      zfyConfigCenter.getValue(THEME_ACTIVE_KEY, this.activeThemeId),
      zfyConfigCenter.getValue(THEME_SETTINGS_KEY, '{}')
    ])

    if (activeThemeValue && this.themeMap.has(activeThemeValue)) {
      this.activeThemeId = activeThemeValue
    }
    this.themeSettings = safeJsonParse(themeSettingsValue || '{}', {})
    this.applyToDocument()
    this.initialized = true
    this.notify()
  }

  async setActiveTheme(themeId, persist = true) {
    if (!this.themeMap.has(themeId)) {
      return
    }
    this.activeThemeId = themeId
    this.applyToDocument()
    this.notify()
    if (persist) {
      await zfyConfigCenter.save(THEME_ACTIVE_KEY, themeId, {
        group: 'theme',
        desc: 'ZFY当前启用主题'
      })
    }
  }

  async updateSetting(key, value, persist = true) {
    const currentThemeId = this.activeThemeId
    const oldSettings = this.themeSettings[currentThemeId] || {}
    this.themeSettings = {
      ...this.themeSettings,
      [currentThemeId]: {
        ...oldSettings,
        [key]: value
      }
    }
    this.applyToDocument()
    this.notify()
    if (persist) {
      await zfyConfigCenter.save(THEME_SETTINGS_KEY, this.themeSettings, {
        group: 'theme',
        desc: 'ZFY主题自定义设置'
      })
    }
  }

  async updateSettings(values = {}, persist = true) {
    const currentThemeId = this.activeThemeId
    const oldSettings = this.themeSettings[currentThemeId] || {}
    this.themeSettings = {
      ...this.themeSettings,
      [currentThemeId]: {
        ...oldSettings,
        ...values
      }
    }
    this.applyToDocument()
    this.notify()
    if (persist) {
      await zfyConfigCenter.save(THEME_SETTINGS_KEY, this.themeSettings, {
        group: 'theme',
        desc: 'ZFY主题自定义设置'
      })
    }
  }

  async resetCurrentThemeSettings() {
    const currentThemeId = this.activeThemeId
    this.themeSettings = {
      ...this.themeSettings,
      [currentThemeId]: {}
    }
    this.applyToDocument()
    this.notify()
    await zfyConfigCenter.save(THEME_SETTINGS_KEY, this.themeSettings, {
      group: 'theme',
      desc: 'ZFY主题自定义设置'
    })
  }
}

export const zfyThemeManager = new ZfyThemeManager()
