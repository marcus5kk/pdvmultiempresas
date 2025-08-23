# Overview

This is a Point of Sale (PDV) system built with PHP and JavaScript that provides retail management capabilities. The system features user authentication, product management, sales processing, and cart functionality. It's designed as a web-based application with a responsive interface for managing retail transactions and inventory.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Frontend Architecture
- **Pure JavaScript Classes**: Uses ES6 classes (PDVSystem, PDVManager) for modular frontend functionality
- **Event-Driven Design**: Implements event delegation and listeners for user interactions
- **Responsive CSS**: Custom CSS with CSS variables for consistent theming and responsive design
- **Component-Based Structure**: Separate JavaScript modules for different system components (main.js for core functionality, pdv.js for point-of-sale operations)

## Backend Architecture
- **PHP-Based API**: RESTful API structure with dedicated endpoints (api/auth.php for authentication)
- **Session Management**: Server-side session handling for user authentication and state persistence
- **MVC Pattern**: Organized with separate pages directory for views and API directory for controllers

## Authentication System
- **Session-Based Authentication**: Uses PHP sessions for user state management
- **Client-Side Session Checking**: JavaScript automatically validates sessions and redirects unauthorized users
- **Login/Logout Flow**: Dedicated login page with form handling and automatic session validation

## Data Management
- **Real-Time Cart Management**: Client-side cart state management with instant updates
- **Product Search**: Dual search functionality supporting both text search and barcode scanning
- **AJAX Communication**: Asynchronous data fetching between frontend and backend
- **Debounced Search**: Performance optimization for search inputs to reduce server requests

# External Dependencies

## Frontend Dependencies
- **Modern Browser APIs**: Relies on fetch API for HTTP requests and ES6 features
- **CSS Grid/Flexbox**: Uses modern CSS layout systems for responsive design

## Potential Backend Dependencies
- **PHP Session Extension**: Required for session management functionality
- **Database System**: Likely requires MySQL or similar database (not explicitly shown in provided files)
- **JSON Handling**: Uses PHP's built-in JSON functions for API responses

## Third-Party Integrations
- **Barcode Scanning Support**: System includes barcode input functionality suggesting potential hardware integration
- **Payment Processing**: Framework in place for payment form handling (specific payment gateway not specified)