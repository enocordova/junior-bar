/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        'jr-green': '#7ed957',
        'jr-brown': '#bf7854',
        'jr-orange': '#efa324',
        'jr-gray': '#4d4a52',
        'kds-bg': '#0c0c0e',
        'kds-card': '#18181b',
        'kds-separator': '#27272a',
      },
    },
  },
  safelist: [
    'bg-jr-green/5', 'bg-jr-green/10', 'bg-jr-green/15', 'bg-jr-green/20',
    'border-jr-green/20', 'border-jr-green/30', 'border-jr-green/40',
    'text-jr-green',
    'border-l-jr-green', 'border-l-jr-brown', 'border-l-jr-orange',
  ],
  plugins: [],
}
