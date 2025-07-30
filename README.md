# Gigcodes Laravel Modular Starter Kit

<div align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB" alt="React">
  <img src="https://img.shields.io/badge/TypeScript-007ACC?style=for-the-badge&logo=typescript&logoColor=white" alt="TypeScript">
  <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
</div>

## 🚀 Overview

The Gigcodes Laravel Modular Starter Kit is a modern, production-ready Laravel application featuring a modular architecture, passwordless authentication with passkeys, two-factor authentication, and a beautiful React + Inertia.js frontend.

## ✨ Key Features

### 🔐 Advanced Authentication
- **Passkey Support** - WebAuthn/FIDO2 passwordless authentication
- **Two-Factor Authentication (2FA)** - TOTP-based with QR code generation
- **Progressive Login Flow** - Email-first authentication with smooth animations
- **Password Recovery** - Secure password reset functionality
- **Email Verification** - Built-in email verification system

### 🏗️ Modular Architecture
- **Module-based Structure** - Clean separation of concerns with dedicated modules
- **Shared Module** - Common components, utilities, and services
- **Auth Module** - Complete authentication system
- **Easy Module Creation** - Structured approach for adding new modules

### 🎨 Modern Frontend Stack
- **React 19** - Latest React features and optimizations
- **Inertia.js** - Seamless SPA experience without API complexity
- **TypeScript** - Full type safety across the frontend
- **Tailwind CSS v4** - Utility-first CSS framework
- **Framer Motion** - Smooth animations and transitions
- **Radix UI** - Accessible, unstyled UI components
- **Dark Mode** - Built-in theme switching with system preference detection

### 🛠️ Developer Experience
- **Hot Module Replacement** - Instant feedback during development
- **TypeScript Code Generation** - Auto-generated types from Laravel models
- **ESLint & Prettier** - Consistent code formatting
- **Laravel Pint** - PHP code styling
- **Vite** - Lightning-fast build tool
- **Concurrent Development** - Run all services with one command

## 📋 Requirements

- PHP 8.2+
- Node.js 18+
- Composer 2.x
- MySQL/PostgreSQL/SQLite

## 🚀 Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/gigcodes/laravel-modular-starter-kit.git
   cd laravel-modular-starter-kit
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed # Optional
   ```

5. **Start development servers**
   ```bash
   composer run dev
   ```

   This single command starts:
   - Laravel development server
   - Vite dev server with HMR
   - Queue worker
   - Laravel Pail for real-time logs

## 📁 Project Structure

```
├── app/                    # Core Laravel application
├── modules/               # Modular components
│   ├── auth/             # Authentication module
│   │   ├── src/          # PHP source files
│   │   ├── resources/    # Module-specific views/assets
│   │   ├── routes/       # Module routes
│   │   └── database/     # Migrations and seeders
│   └── shared/           # Shared module
│       ├── src/          # Common services/utilities
│       └── resources/    # Shared components/layouts
├── resources/            # Global resources
├── database/             # Main database files
└── public/               # Public assets
```

## 🔧 Module System

### Creating a New Module

1. Create module directory structure:
   ```bash
   php artisan make:module MyModule
   ```

2. Register the module in `composer.json`:
   ```json
   "repositories": [
     {
       "type": "path",
       "url": "modules/my-module"
     }
   ]
   ```

3. Add to autoload:
   ```json
   "autoload": {
     "psr-4": {
       "Modules\\MyModule\\": "modules/my-module/src/"
     }
   }
   ```

## 🔐 Authentication Features

### Passkeys
- Browser-based biometric authentication
- FIDO2/WebAuthn compliant
- Automatic fallback for unsupported browsers
- Multiple passkey management per user

### Two-Factor Authentication
- TOTP-based authentication
- QR code generation for authenticator apps
- Recovery codes for account recovery
- Seamless integration with login flow

## 🎨 UI Components

The starter kit includes a comprehensive set of reusable UI components:

- **Layout Components**: AppShell, Sidebar, Header
- **Form Components**: Input, Select, Checkbox, Form validation
- **Feedback**: Alert, Toast notifications
- **Navigation**: Breadcrumbs, Navigation menu
- **Data Display**: Cards, Badges, Avatars
- **Overlays**: Modal, Sheet, Dropdown menu

## 📜 Available Scripts

### Backend
- `composer run dev` - Start all development services
- `composer run test` - Run tests
- `composer run lint` - Fix PHP code style
- `composer run refactor` - Run Rector for code improvements

### Frontend
- `npm run dev` - Start Vite dev server
- `npm run build` - Build for production
- `npm run lint` - Fix JS/TS code style
- `npm run format` - Format code with Prettier
- `npm run types` - Check TypeScript types

## 🔍 Key Technologies

- **Laravel 12** - The PHP framework for web artisans
- **Inertia.js** - The modern monolith
- **React 19** - A JavaScript library for building user interfaces
- **TypeScript** - JavaScript with syntax for types
- **Tailwind CSS v4** - A utility-first CSS framework
- **Vite** - Next generation frontend tooling
- **Ziggy** - JavaScript route handling for Laravel
- **Spatie Laravel Data** - Powerful data objects for Laravel

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Credits

Created and maintained by [Gigcodes](https://gigcodes.com)

---

<div align="center">
  <p>Built with ❤️ by Gigcodes</p>
</div>