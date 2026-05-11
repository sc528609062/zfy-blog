import { writeFileSync } from "node:fs";

const pages = [
  ["home", "homepage with hero, channels, mixed content feed, right sidebar rankings and VIP card"],
  ["posts-channel", "posts channel listing page with filters, category tabs, article cards and sidebar"],
  ["images-channel", "image gallery channel page with masonry grid, tags, creator cards and lightbox hints"],
  ["files-channel", "download resource channel page with resource cards, prices, VIP discounts and rankings"],
  ["category-page", "category aggregation page with breadcrumb, filters, sorting and mixed content grid"],
  ["tag-page", "tag aggregation page with tag header, related tags and content feed"],
  ["content-detail", "unified article content detail page with rich text, author info, comments, related content and hidden VIP block"],
  ["file-detail", "resource download detail page with preview gallery, download sources, price, VIP rules and comments"],
  ["images-detail", "image gallery detail page with masonry images, author info, tags, comments and related galleries"],
  ["page-detail", "independent static page such as privacy/about/terms, clean reading layout"],
  ["search", "search results page with search bar, filters, content/resource/user tabs and result cards"],
  ["rank", "ranking page for articles, resources, authors, downloads and weekly hot lists"],
  ["vip", "VIP membership purchase page with pricing cards, benefits, comparison table and payment panel"],
  ["points-store", "points mall page with exchange items, points balance, tasks and redemption history"],
  ["authors", "authors listing page with creator cards, categories, follow buttons and earnings/popularity stats"],
  ["author-profile", "author profile page with banner, avatar, portfolio tabs, resource shelf and fan stats"],
  ["links", "friend links page with grouped link cards, submit link CTA and site badges"],
  ["login", "login page with email/password, social login placeholders and brand visual panel"],
  ["register", "registration page with email verification, username, password and benefits panel"],
  ["user-overview", "user center overview with profile banner, wallet, points, VIP, recent orders/downloads"],
  ["user-orders", "my orders page with order table/cards, status filters and payment actions"],
  ["user-downloads", "my downloads page with downloaded resources, access status, retry/download buttons"],
  ["user-wallet", "wallet and balance page with recharge, transactions, balance chart and payment methods"],
  ["user-points", "points details page with points balance, earning tasks, transaction list and mall shortcuts"],
  ["user-vip", "my membership page with VIP status, renewal, benefits, download quota and invoices"],
  ["author-workspace", "author workspace with submissions, earnings, withdrawal, content performance and tasks"],
  ["admin-dashboard", "admin dashboard with sidebar, KPIs, charts, pending reviews, orders and system health"],
  ["admin-contents", "admin content list page with filters, table, bulk actions, status chips and quick edit"],
  ["admin-editor", "TipTap ProseMirror three-mode editor with block toolbar, canvas, publish sidebar, SEO and monetization settings"],
  ["admin-media", "admin media library with folder tree, upload area, media grid and detail drawer"],
  ["admin-comments", "comments moderation page with review queue, filters, report handling and reply panel"],
  ["admin-orders", "orders and payments management page with revenue KPIs, order table, gateway status and logs"],
  ["admin-users", "users and roles management page with user table, role filters, VIP status and ban actions"],
  ["admin-themes", "theme management page with uploaded themes, preview cards, active theme settings and install button"],
  ["admin-page-builder", "page builder admin page with component palette, canvas layout, responsive preview and settings inspector"],
  ["admin-plugins", "plugin management page with plugin cards, permissions, enable disable actions and hook status"],
  ["installer", "web installer page with environment checks, database settings, admin creation and progress steps"],
  ["updater", "upgrade center page with current version, available update, changelog, backup warning and migration progress"]
];

const styles = [
  {
    key: "a",
    dir: "style-a-blue-gaming",
    prompt: "Style A: blue-white gaming resource community like a modern Chinese game resource site. Clean white background, bright blue primary, soft rounded cards, game/resource imagery, dense but organized, top nav with search, VIP button, notifications, avatar. Professional high fidelity UI screenshot."
  },
  {
    key: "b",
    dir: "style-b-marketplace",
    prompt: "Style B: blue-white marketplace and resource commerce style. Strong mall/resource trading feeling, product cards, shopping cart, coupons, rankings, VIP price tags, clean SaaS marketplace layout, rounded panels, polished Chinese interface."
  },
  {
    key: "c",
    dir: "style-c-creative",
    prompt: "Style C: cheerful creative resource community. Yellow, pink and sky blue accents, cute sticker-like illustrations, friendly creator platform, clean white cards, playful but professional, resource grid and creator community feeling."
  }
];

const common = "Use case: ui-mockup. Asset type: zfy-blog complete system desktop UI concept. Site name: zfy-blog. Chinese web interface. No browser chrome, no watermark, no real brand logos, avoid exact copyrighted game logos, readable structured UI, high fidelity screenshot.";

const lines = [];

for (const style of styles) {
  for (const [slug, description] of pages) {
    lines.push(JSON.stringify({
      out: `${style.dir}/${slug}.png`,
      model: "gpt-image-2",
      quality: "medium",
      size: "1536x1024",
      prompt: `${common} ${style.prompt} Page: ${description}. Include realistic Chinese labels and plausible fake data.`
    }));
  }
}

writeFileSync("tmp/imagegen/zfy-complete-system.jsonl", lines.join("\n") + "\n", "utf8");
console.log(`wrote ${lines.length} jobs`);
