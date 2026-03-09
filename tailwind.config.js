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
  plugins: [],
}
