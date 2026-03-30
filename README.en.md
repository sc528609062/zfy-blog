# zfy-blog

A blog system developed based on the RuoYi framework.

## Project Introduction

zfy-blog is a personal blog system built on the RuoYi framework, offering features such as article management, category management, tag management, and topic management. The system adopts a frontend-backend separation architecture, with the backend powered by Spring Boot + MyBatis and the frontend built with Vue.js.

## Technology Stack

### Backend Technologies
- Spring Boot 2.5.x
- MyBatis + MyBatis-Plus
- Druid Database Connection Pool
- Redis Cache
- JWT Authentication
- Swagger API Documentation

### Frontend Technologies
- Vue.js 3.x
- Element UI
- Axios
- Vue Router

## Project Structure

```
zfy-blog/
├── ruoyi-admin/          # Main application module
├── ruoyi-blog/           # Blog business module
│   ├── controller/       # Blog controllers
│   ├── domain/           # Entity classes
│   ├── mapper/           # Data access layer
│   └── service/          # Service layer
├── ruoyi-common/         # Common module
│   ├── annotation/       # Custom annotations
│   ├── constant/         # Constant definitions
│   ├── core/             # Core classes
│   ├── enums/            # Enumerations
│   ├── exception/        # Exception handling
│   ├── filter/           # Filters
│   └── utils/            # Utility classes
├── ruoyi-framework/      # Framework module
│   ├── aspectj/          # AOP aspects
│   ├── config/           # Configuration classes
│   ├── datasource/       # Multi-datasource
│   ├── interceptor/      # Interceptors
│   ├── manager/          # Managers
│   ├── security/         # Security authentication
│   └── web/              # Web-related components
├── ruoyi-generator/      # Code generation module
├── ruoyi-quartz/         # Scheduled tasks module
├── ruoyi-system/         # System business module
│   ├── domain/           # System entities
│   ├── mapper/           # Data access
│   └── service/          # Service layer
└── ruoyi-ui/             # Frontend project
```

## Key Features

### Blog Management
- **Article Management**: Create, read, update, delete articles; publish/unpublish; pin/recommend
- **Category Management**: Article categories with support for multi-level categories
- **Tag Management**: Management of article tags
- **Topic Management**: Blog topic management
- **Comment Management**: Article comment functionality
- **Configuration Management**: Blog configuration settings

### User Interaction
- **Article Likes**: Support for liking/unliking articles
- **Article Favorites**: Support for favoriting/unfavoriting articles
- **View Statistics**: Article view count tracking

### System Functions
- **User Management**: User account management
- **Role Management**: Role and permission management
- **Menu Management**: System menu configuration
- **Department Management**: Organizational structure management
- **Position Management**: Position information management

### Monitoring & Management
- **Online Users**: Monitor currently logged-in users
- **Scheduled Tasks**: Task scheduling management
- **System Logs**: Operation log recording

## Quick Start

### Environment Requirements
- JDK 1.8+
- MySQL 5.7+
- Redis 5.0+
- Node.js 14+ (Frontend)
- Maven 3.6+ (Backend)

### Backend Configuration

1. Create the database
```sql
CREATE DATABASE IF NOT EXISTS zfy_blog DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Modify database configuration (ruoyi-admin/src/main/resources/application-druid.yml)

3. Start the project
```bash
cd ruoyi-admin
mvn clean package
java -jar ruoyi-admin.jar
```

### Frontend Configuration

1. Install dependencies
```bash
cd ruoyi-ui
npm install
```

2. Start the development server
```bash
npm run dev
```

3. Build for production
```bash
npm run build
```

## API Endpoints

### Blog APIs
| Endpoint | Method | Description |
|----------|--------|-------------|
| /blog/article/public/list | GET | Get list of public articles |
| /blog/article/{articleId} | GET | Get article details |
| /blog/article/like/{articleId} | POST | Like an article |
| /blog/article/favorite/{articleId} | POST | Favorite an article |
| /blog/category/list | GET | Get category list |
| /blog/tag/list | GET | Get tag list |
| /blog/topic/public/list | GET | Get topic list |

### System APIs
| Endpoint | Method | Description |
|----------|--------|-------------|
| /login | POST | User login |
| /getInfo | GET | Get user information |
| /system/user/list | GET | Get user list |

## Related Documentation

- RuoYi Official Documentation: https://doc.ruoyi.vip/
- Vue.js Documentation: https://vuejs.org/
- Element UI Documentation: https://element.eleme.io/

## License

MIT License

## Community Support

For questions or suggestions, please submit an Issue or contact the developer.