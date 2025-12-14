/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/**/*.{html,php,js}",
    "./src/**/*.php",
    "./*.php"
  ],
  theme: {
    screens: {
      'sm': '640px',
      'md': '991px',  // Переопределяем md на 991px
      'lg': '1280px',
      'xl': '1536px',
    },
    extend: {
      colors: {
        "primary": "#10b77f",
        "primary-hover": "#0e9f6e",
        "background-light": "#f6f8f7",
        "background-dark": "#10221c",
        "text-primary": "#111816",
        "text-secondary": "#61897c",
      },
      fontFamily: {
        "display": ["Manrope", "sans-serif"]
      },
      borderRadius: {
        "DEFAULT": "0.5rem",
        "lg": "1rem",
        "xl": "1.5rem",
        "2xl": "2rem",
        "full": "9999px"
      },
    },
  },
  darkMode: "class",
  plugins: [],
}
