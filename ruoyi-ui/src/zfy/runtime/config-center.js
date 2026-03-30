import { addConfig, getConfig, listConfig, updateConfig } from '@/api/blog'

function safeJsonParse(value, fallback) {
  try {
    return JSON.parse(value)
  } catch (error) {
    return fallback
  }
}

function serializeConfigValue(value) {
  if (value === undefined || value === null) {
    return ''
  }
  if (typeof value === 'string') {
    return value
  }
  return JSON.stringify(value)
}

class ZfyConfigCenter {
  constructor() {
    this.configMap = new Map()
    this.loadedAll = false
  }

  loadToCache(record) {
    if (record && record.configKey) {
      this.configMap.set(record.configKey, record)
    }
  }

  async getByKey(configKey, fallback = '') {
    try {
      const response = await getConfig(configKey)
      const record = response && response.data ? response.data : null
      if (record && record.configKey) {
        this.loadToCache(record)
        return record
      }
      return {
        configKey,
        configValue: serializeConfigValue(fallback)
      }
    } catch (error) {
      return {
        configKey,
        configValue: serializeConfigValue(fallback)
      }
    }
  }

  async getValue(configKey, fallback = '') {
    const record = await this.getByKey(configKey, fallback)
    return record && record.configValue !== undefined ? record.configValue : fallback
  }

  async getJson(configKey, fallback = {}) {
    const raw = await this.getValue(configKey, '')
    if (!raw) {
      return fallback
    }
    return safeJsonParse(raw, fallback)
  }

  async listAll(force = false) {
    if (this.loadedAll && !force) {
      return Array.from(this.configMap.values())
    }
    try {
      const response = await listConfig()
      const rows = response && response.rows ? response.rows : []
      this.configMap.clear()
      rows.forEach(row => this.loadToCache(row))
      this.loadedAll = true
      return rows
    } catch (error) {
      return Array.from(this.configMap.values())
    }
  }

  async save(configKey, value, options = {}) {
    const payloadValue = serializeConfigValue(value)
    const group = options.group || 'zfy'
    const desc = options.desc || configKey

    let current = this.configMap.get(configKey)
    if (!current) {
      current = await this.getByKey(configKey, '')
      if (!current.configId) {
        await addConfig({
          configKey,
          configValue: payloadValue,
          configDesc: desc,
          configGroup: group
        })
        this.loadedAll = false
        await this.listAll(true)
        return
      }
    }

    await updateConfig({
      ...current,
      configKey,
      configValue: payloadValue,
      configDesc: current.configDesc || desc,
      configGroup: current.configGroup || group
    })

    this.loadToCache({
      ...current,
      configKey,
      configValue: payloadValue,
      configDesc: current.configDesc || desc,
      configGroup: current.configGroup || group
    })
  }
}

const zfyConfigCenter = new ZfyConfigCenter()

export { zfyConfigCenter, safeJsonParse }
