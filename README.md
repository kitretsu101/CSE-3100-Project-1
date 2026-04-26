# CSE-3100-Project-1

A complete Bit2byte coding club website built with vanilla JavaScript, PHP, and modern web technologies.

## Features

### Frontend (Vanilla JavaScript)
- **Typewriter Effect**: Animated hero subtitle
- **Sticky Navbar**: Smooth navigation with backdrop blur
- **Toast Notifications**: User feedback system
- **Stats Counter**: Animated number counters with intersection observer
- **Skeleton Loading**: Smooth loading states for team section
- **Card Tilt Effects**: Interactive 3D hover effects
- **LocalStorage Management**: Form data persistence
- **Scroll Animations**: Fade-in effects on scroll
- **Dynamic Events**: Real-time event loading and RSVP system

### Backend (PHP)
- **Contact Form**: AJAX submission with file logging
- **Member Authentication**: Secure login/registration with password hashing
- **Session Management**: Protected member areas
- **Events API**: Full CRUD operations for event management
- **RSVP System**: Members can register for events

### Admin Features
- **Event Management Dashboard**: Add, edit, delete events
- **Real-time Updates**: Changes reflect immediately on the website
- **User Authentication**: Admin access for logged-in members

## File Structure

```
├── index.html          # Main website
├── style.css           # Base styles
├── animations.css      # Animation styles
├── main.js            # Frontend JavaScript
├── events.json        # Event data storage
├── admin.php          # Admin dashboard
├── auth.php           # Authentication helpers
├── events-api.php     # Events CRUD API
├── get-user-info.php  # User info endpoint
├── contact-submit.php # Contact form handler
├── login.php          # Login page
├── register.php       # Registration page
├── members.php        # Protected member area
├── logout.php         # Logout handler
└── users.txt          # User data storage
```

## Setup

1. Ensure PHP is installed and configured
2. Place all files in your web server directory
3. Make sure PHP has write permissions for data files
4. Access `index.html` in your browser

## Usage

### For Visitors
- Browse events and club information
- Contact the club via the contact form
- Register as a member to access exclusive features

### For Members
- Login to access member-only areas
- RSVP for upcoming events
- View event attendance counts

### For Admins
- Login and access the admin dashboard at `/admin.php`
- Add new events with title, date, description, and image
- Edit existing events
- Delete events
- View RSVP statistics

## Technologies Used

- **HTML5**: Semantic markup
- **CSS3**: Modern styling with animations
- **Vanilla JavaScript**: ES6+ features, no frameworks
- **PHP**: Server-side processing and authentication
- **JSON**: Data storage for events
- **Fetch API**: AJAX requests
- **Intersection Observer**: Scroll-based animations
- **LocalStorage**: Client-side data persistence