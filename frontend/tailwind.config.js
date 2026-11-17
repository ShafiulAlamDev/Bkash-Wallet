/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        bkash: {
          DEFAULT: '#e2136e',
          dark:    '#a0094f',
          light:   '#fce4ec',
        }
      }
    }
  },
  plugins: []
}
