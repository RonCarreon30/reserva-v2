/** @type {import('tailwindcss').Config} */
module.exports = {
  // Specify the files Tailwind CSS should scan for utility classes
  content: ["./build/**/*.{html,js,php}"],

  // Extend the default theme with customizations
  theme: {
    extend: {
      // Define custom colors
      colors: {
        "plv-blue": "#001663",
        "plv-highlight": "#4c4c70",
        "persian-blue": "#002ccc",
      },
      // Define background images
      backgroundImage: {
        "hero-pattern": "url('/img/bg-image.jpg')",
      },
    },
  },

  // Specify any additional plugins (if needed)
  plugins: ["prettier-plugin-tailwindcss"],
};
