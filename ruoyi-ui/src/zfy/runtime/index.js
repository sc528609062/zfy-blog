import { zfyConfigCenter } from './config-center'
import { zfyThemeCatalog } from './theme-catalog'
import { zfyThemeManager } from './theme-manager'
import { zfyPluginCatalog } from './plugin-catalog'
import { zfyPluginManager } from './plugin-manager'

let runtimeInstalled = false

export function installZfyRuntime(Vue) {
  if (runtimeInstalled) {
    return
  }
  runtimeInstalled = true

  zfyThemeManager.registerThemes(zfyThemeCatalog)
  zfyPluginManager.registerPlugins(zfyPluginCatalog)

  Vue.prototype.$zfyConfigCenter = zfyConfigCenter
  Vue.prototype.$zfyThemeRuntime = zfyThemeManager
  Vue.prototype.$zfyPluginRuntime = zfyPluginManager

  Promise.all([
    zfyThemeManager.initialize(),
    zfyPluginManager.initialize()
  ]).catch(() => {})
}

export {
  zfyConfigCenter,
  zfyThemeManager,
  zfyPluginManager,
  zfyThemeCatalog,
  zfyPluginCatalog
}
