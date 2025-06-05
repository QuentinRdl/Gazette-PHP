# Gazette-PHP

**Gazette-PHP** is a PHP-based web application developed as a student project. It simulates a news publishing platform, allowing users to read articles and administrators to manage content through a simple interface.

## Features

- **Article Management**: Create, edit, and delete news articles.
- **User Authentication**: Secure login system for administrators.
- **Responsive Design**: Accessible on various devices with a clean layout.
- **Dockerized Environment**: Easily deployable using Docker.

## Project Structure

- `index.php`: Main entry point displaying articles.
- `admin/`: Contains administrative interfaces for content management.
- `includes/`: Shared components like headers, footers, and database connections.
- `styles/`: CSS files for styling the application.
- `Dockerfile`: Configuration for containerizing the application.

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL or compatible database
- Docker (optional, for containerized setup)

### Setup Instructions

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/QuentinRdl/Gazette-PHP.git
   cd Gazette-PHP
