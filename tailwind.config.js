/** @type {import('tailwindcss').Config} */
module.exports = {
  prefix: 'db-',
  content: [
    './admin/**/*.php',
    './includes/**/*.php',
    './assets/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        'primary': '#A5B4FC',
        'primary-hover': '#818CF8',
        'primary-light': '#E0E7FF',
        'secondary': '#C4B5FD',
        'secondary-hover': '#A78BFA',
        'secondary-light': '#EDE9FE',
        'success': '#86EFAC',
        'success-hover': '#4ADE80',
        'success-light': '#DCFCE7',
        'warning': '#FDE68A',
        'warning-hover': '#FBBF24',
        'warning-light': '#FEF3C7',
        'danger': '#FCA5A5',
        'danger-hover': '#F87171',
        'danger-light': '#FEE2E2',
        'info': '#7DD3FC',
        'info-hover': '#38BDF8',
        'info-light': '#E0F2FE',
      },
      borderRadius: {
        'DEFAULT': '12px',
        'lg': '16px',
        'sm': '6px',
      },
      boxShadow: {
        'DEFAULT': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
        'lg': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
      },
    },
  },
  plugins: [],
  corePlugins: {
    preflight: false,
  },
}
