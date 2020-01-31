module.exports = {
  theme: {

    extend: {
      colors: {
        primary: "var(--color-primary)",
        secondary: "var(--color-secondary)",
        success: "var(--color-success)",
        warning: "var(--color-warning)",
        danger: "var(--color-danger)",
      },
    }
  },
  variants: {},
  plugins: [
    opacity: ['hover', 'focus', 'disabled'],
    cursor: ['disabled']
  ]
};
