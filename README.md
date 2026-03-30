

# zfy-blog

基于 RuoYi 若依框架开发的博客系统

## 项目介绍

zfy-blog 是一个基于 RuoYi 若依框架开发的个人博客系统，提供文章管理、分类管理、标签管理、专题管理等功能。系统采用前后端分离架构，后端使用 Spring Boot + MyBatis，前端使用 Vue.js。

## 技术栈

### 后端技术
- Spring Boot 2.5.x
- MyBatis + MyBatis-Plus
- Druid 数据库连接池
- Redis 缓存
- JWT 认证
- Swagger API 文档

### 前端技术
- Vue.js 3.x
- Element UI
- Axios
- Vue Router

## 项目结构

```
zfy-blog/
├── ruoyi-admin/          # 应用主模块
├── ruoyi-blog/           # 博客业务模块
│   ├── controller/       # 博客控制器
│   ├── domain/          # 实体类
│   ├── mapper/          # 数据访问层
│   └── service/        # 服务层
├── ruoyi-common/        # 公共模块
│   ├── annotation/      # 自定义注解
│   ├── constant/      # 常量定义
│   ├── core/          # 核心类
│   ├── enums/        # 枚举
│   ├── exception/    # 异常处理
│   ├── filter/       # 过滤器
│   └── utils/        # 工具类
├── ruoyi-framework/     # 框架模块
│   ├── aspectj/      # AOP切面
│   ├── config/       # 配置类
│   ├── datasource/  # 多数据源
│   ├── interceptor/ # 拦截器
│   ├── manager/     # 管理器
│   ├── security/    # 安全认证
│   └── web/         # Web相关
├── ruoyi-generator/    # 代码生成模块
├── ruoyi-quartz/       # 定时任务模块
├── ruoyi-system/       # 系统业务模块
│   ├── domain/       # 系统实体
│   ├── mapper/      # 数据访问
│   └── service/     # 服务层
└── ruoyi-ui/           # 前端项目
```

## 主要功能

### 博客管理
- **文章管理**: 文章的增删改查、发布下架、置顶推荐
- **分类管理**: 文章分类，支持多级分类
- **标签管理**: 文章标签管理
- **专题管理**: 博客专题管理
- **评论管理**: 文章评论功能
- **配置管理**: 博客配置信息

### 用户交互
- **文章点赞**: 支持点赞/取消点赞
- **文章收藏**: 支持收藏/取消收藏
- **浏览统计**: 文章浏览量统计

### 系统功能
- **用户管理**: 用户账号管理
- **角色管理**: 角色权限管理
- **菜单管理**: 系统菜单配置
- **部门管理**: 组织架构管理
- **岗位管理**: 岗位信息管理

### 监控管理
- **在线用户**: 当前登录用户监控
- **定时任务**: 任务调度管理
- **系统日志**: 操作日志记录

## 快速开始

### 环境要求
- JDK 1.8+
- MySQL 5.7+
- Redis 5.0+
- Node.js 14+ (前端)
- Maven 3.6+ (后端)

### 后端配置

1. 创建数据库
```sql
CREATE DATABASE IF NOT EXISTS zfy_blog DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. 修改数据库配置 (ruoyi-admin/src/main/resources/application-druid.yml)

3. 启动项目
```bash
cd ruoyi-admin
mvn clean package
java -jar ruoyi-admin.jar
```

### 前端配置

1. 安装依赖
```bash
cd ruoyi-ui
npm install
```

2. 启动开发服务器
```bash
npm run dev
```

3. 构建生产环境
```bash
npm build
```

## API 接口

### 博客接口
| 接口路径 | 方法 | 说明 |
|---------|------|------|
| /blog/article/public/list | GET | 获取公开文章列表 |
| /blog/article/{articleId} | GET | 获取文章详情 |
| /blog/article/like/{articleId} | POST | 点赞文章 |
| /blog/article/favorite/{articleId} | POST | 收藏文章 |
| /blog/category/list | GET | 获取分类列表 |
| /blog/tag/list | GET | 获取标签列表 |
| /blog/topic/public/list | GET | 获取专题列表 |

### 系统接口
| 接口路径 | 方法 | 说明 |
|---------|------|------|
| /login | POST | 用户登录 |
| /getInfo | GET | 获取用户信息 |
| /system/user/list | GET | 用户列表 |

## 相关文档

- RuoYi 官方文档: https://doc.ruoyi.vip/
- Vue.js 文档: https://vuejs.org/
- Element UI 文档: https://element.eleme.io/

## 许可证

MIT License

## 交流群

如有疑问或建议，请提交 Issue 或联系开发者。