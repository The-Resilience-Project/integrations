// Mermaid only supports hex/rgb/hsl — not OKLCH.
// These hex values approximate the dashboard's OKLCH dark theme.
export const MERMAID_CONFIG = {
  theme: 'dark' as const,
  themeVariables: {
    primaryColor: '#1a3038',
    primaryTextColor: '#eeedf2',
    primaryBorderColor: '#4db8b8',
    lineColor: '#5a8a8a',
    secondaryColor: '#1e1e28',
    tertiaryColor: '#181822',
    background: '#111118',
    mainBkg: '#1a3038',
    nodeBorder: '#3a8a8a',
    clusterBkg: '#181822',
    clusterBorder: '#2a2a38',
    titleColor: '#d0cfd8',
    edgeLabelBackground: '#181822',
    nodeTextColor: '#eeedf2',
  },
  flowchart: {
    curve: 'basis' as const,
    padding: 16,
  },
  fontFamily: 'system-ui, sans-serif',
  fontSize: 14,
};
