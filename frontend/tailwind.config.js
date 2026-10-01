/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Cream palette
        cream: {
          50: '#FFFBF0',
          100: '#FFF8E7',
          200: '#FFE8C8',
          300: '#FFD9A3',
          400: '#FFC875',
          500: '#FFB347',
          600: '#E69C2F',
          700: '#CC8825',
          800: '#997A1F',
          900: '#664F15',
        },
        // Terracotta palette
        terracotta: {
          50: '#FFF5F0',
          100: '#FEE8DC',
          200: '#FDD0B2',
          300: '#FCB587',
          400: '#FB9A5C',
          500: '#FA8231',
          600: '#E67821',
          700: '#CC6B19',
          800: '#995013',
          900: '#66360D',
        },
        // Warm earth tones
        earth: {
          light: '#F5E6D3',
          medium: '#D4A574',
          dark: '#8B6F47',
        },
        // Alias 'primary' -> terracotta. Beberapa komponen lama (style.css)
        // memakai bg-primary-*, supaya tidak gagal build kita alias ke sini.
        primary: {
          50: '#FFF5F0',
          100: '#FEE8DC',
          200: '#FDD0B2',
          300: '#FCB587',
          400: '#FB9A5C',
          500: '#FA8231',
          600: '#E67821',
          700: '#CC6B19',
          800: '#995013',
          900: '#66360D',
        },
      },
      backgroundColor: {
        'gradient-cream': 'linear-gradient(to right, #FFF8E7, #FFE8C8)',
        'gradient-terracotta': 'linear-gradient(to right, #FA8231, #FB9A5C)',
      },
      textColor: {
        'cream': '#FFB347',
        'terracotta': '#FA8231',
      },
      borderColor: {
        'cream': '#FFB347',
        'terracotta': '#FA8231',
      },
      shadows: {
        'warm': '0 4px 15px rgba(250, 130, 49, 0.15)',
        'cream': '0 4px 15px rgba(255, 179, 71, 0.15)',
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'Segoe UI', 'system-ui', 'sans-serif'],
        body: ['Plus Jakarta Sans', 'Segoe UI', 'system-ui', 'sans-serif'],
        heading: ['Fraunces', 'Georgia', 'serif'],
        display: ['Fraunces', 'Georgia', 'serif'],
      },
      // Ditambahkan supaya .modal-overlay / .modal-content di style.css tidak
      // gagal build (dulu referensi ke class yang belum pernah didefinisikan).
      boxShadow: {
        card: '0 20px 60px -15px rgba(0,0,0,0.3)',
      },
      keyframes: {
        'fade-in': {
          '0%': { opacity: 0 },
          '100%': { opacity: 1 },
        },
        'slide-up': {
          '0%': { transform: 'translateY(16px)', opacity: 0 },
          '100%': { transform: 'translateY(0)', opacity: 1 },
        },
      },
      animation: {
        'fade-in': 'fade-in 0.2s ease-out',
        'slide-up': 'slide-up 0.25s ease-out',
      },
    },
  },
  plugins: [],
}