/**
 * Spimes主题配置
 * 参考Typecho Spimes主题的视觉风格设计
 */

export default {
  name: 'spimes',
  title: 'Spimes主题风格',
  version: '1.0.0',
  author: 'zfy',
  description: '参考Typecho Spimes主题设计的Vue风格主题',

  // 主题颜色配置
  colors: {
    primary: '#409eff',      // 主题色
    primaryHover: '#66b1ff',
    success: '#67c23a',      // 成功色
    warning: '#e6a23c',      // 警告色
    danger: '#f56c6c',       // 危险色
    info: '#909399',         // 信息色

    // 文本颜色
    textPrimary: '#303133',
    textRegular: '#606266',
    textSecondary: '#909399',
    textPlaceholder: '#C0C4CC',

    // 边框颜色
    borderColor: '#DCDFE6',
    borderColorLight: '#E4E7ED',
    borderColorLighter: '#EBEEF5',
    borderColorExtraLight: '#F2F6FC',

    // 背景颜色
    background: '#f8f9fa',          // 页面背景
    backgroundWhite: '#ffffff',      // 白色背景
    backgroundGray: '#f0f2f5',       // 灰色背景
  },

  // 字体配置
  fonts: {
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
    fontSizeBase: '14px',
    fontSizeSmall: '12px',
    fontSizeMedium: '14px',
    fontSizeLarge: '16px',
    fontSizeXLarge: '18px',
    fontSizeXXLarge: '20px',

    // 行高
    lineHeightBase: 1.5,
    lineHeightLarge: 1.8,
  },

  // 间距配置
  spacing: {
    xs: '4px',
    sm: '8px',
    md: '12px',
    lg: '16px',
    xl: '20px',
    xxl: '30px',
  },

  // 圆角配置
  borderRadius: {
    small: '4px',
    medium: '6px',
    large: '8px',
    xLarge: '12px',
  },

  // 阴影配置
  shadow: {
    small: '0 2px 4px rgba(0, 0, 0, 0.08)',
    medium: '0 2px 8px rgba(0, 0, 0, 0.1)',
    large: '0 4px 16px rgba(0, 0, 0, 0.12)',
    xLarge: '0 8px 24px rgba(0, 0, 0, 0.15)',
  },

  // 动画配置
  transition: {
    fast: '0.2s ease',
    base: '0.3s ease',
    slow: '0.5s ease',
  }
}
