const zfyAuroraTheme = {
  id: 'zfy-aurora',
  name: 'ZFY Aurora',
  description: '参考 WordPress 主题视觉层级，强调玻璃感导航与卡片化信息结构。',
  previewColor: '#f04494',
  settings: [
    { key: 'accentColor', label: '强调色', type: 'color', default: '#f04494' },
    { key: 'mainMaxWidth', label: '内容宽度', type: 'number', default: 1200, min: 960, max: 1600, step: 10, suffix: 'px' },
    { key: 'cardRadius', label: '卡片圆角', type: 'number', default: 12, min: 4, max: 30, step: 1, suffix: 'px' },
    { key: 'enableBlurHeader', label: '启用毛玻璃导航', type: 'switch', default: true },
    { key: 'pageBackground', label: '页面背景', type: 'color', default: '#f5f6f7' },
    { key: 'contentBackground', label: '内容背景', type: 'color', default: '#ffffff' }
  ],
  tokens: {
    accentColor: '#f04494',
    mainMaxWidth: 1200,
    cardRadius: 12,
    enableBlurHeader: true,
    pageBackground: '#f5f6f7',
    contentBackground: '#ffffff',
    textPrimary: '#333333',
    textSecondary: '#4e5358',
    textMuted: '#777777',
    borderColor: 'rgba(50, 50, 50, 0.08)',
    shadowColor: 'rgba(116, 116, 116, 0.12)',
    focusShadowColor: 'rgba(253, 83, 161, 0.35)'
  }
}

const zfyMistTheme = {
  id: 'zfy-mist',
  name: 'ZFY Mist',
  description: '偏冷静配色，强调阅读与长文内容连续性。',
  previewColor: '#2a83ff',
  settings: [
    { key: 'accentColor', label: '强调色', type: 'color', default: '#2a83ff' },
    { key: 'mainMaxWidth', label: '内容宽度', type: 'number', default: 1260, min: 960, max: 1600, step: 10, suffix: 'px' },
    { key: 'cardRadius', label: '卡片圆角', type: 'number', default: 10, min: 4, max: 30, step: 1, suffix: 'px' },
    { key: 'enableBlurHeader', label: '启用毛玻璃导航', type: 'switch', default: true },
    { key: 'pageBackground', label: '页面背景', type: 'color', default: '#f2f5f8' },
    { key: 'contentBackground', label: '内容背景', type: 'color', default: '#ffffff' }
  ],
  tokens: {
    accentColor: '#2a83ff',
    mainMaxWidth: 1260,
    cardRadius: 10,
    enableBlurHeader: true,
    pageBackground: '#f2f5f8',
    contentBackground: '#ffffff',
    textPrimary: '#1d2a3b',
    textSecondary: '#3f4c5d',
    textMuted: '#708090',
    borderColor: 'rgba(40, 70, 100, 0.1)',
    shadowColor: 'rgba(41, 89, 148, 0.12)',
    focusShadowColor: 'rgba(42, 131, 255, 0.28)'
  }
}

export const zfyThemeCatalog = [zfyAuroraTheme, zfyMistTheme]
