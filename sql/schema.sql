-- ============================================================================
-- News CMS — database schema + demo seed data
-- Import this once into an empty MySQL/MariaDB database (cPanel → phpMyAdmin
-- → Import, or `mysql -u user -p dbname < schema.sql`).
--
-- No admin account is created here on purpose (shipping a default
-- username/password in a public SQL file is a bad habit). After importing,
-- open /install.php on your site once — it will let you create the first
-- admin account, then lock itself.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- ── admins ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(190) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until   DATETIME NULL DEFAULT NULL,
  last_login_at  DATETIME NULL DEFAULT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── categories ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  slug        VARCHAR(160) NOT NULL UNIQUE,
  description TEXT NULL,
  color       VARCHAR(7) NOT NULL DEFAULT '#1d4ed8',
  image_url   VARCHAR(500) NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_categories_sort (sort_order),
  KEY idx_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── tags ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tags (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── articles ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS articles (
  id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title                   VARCHAR(255) NOT NULL,
  slug                    VARCHAR(255) NOT NULL UNIQUE,
  excerpt                 TEXT NULL,
  content                 LONGTEXT NOT NULL,
  content_text            LONGTEXT NULL,
  status                  ENUM('draft','scheduled','published','archived') NOT NULL DEFAULT 'draft',
  featured_image_url      VARCHAR(500) NULL,
  featured_image_caption  VARCHAR(300) NULL,
  video_url               VARCHAR(500) NULL,
  category_id             INT UNSIGNED NULL,
  author_name             VARCHAR(120) NULL,
  is_featured             TINYINT(1) NOT NULL DEFAULT 0,
  is_breaking             TINYINT(1) NOT NULL DEFAULT 0,
  is_hero                 TINYINT(1) NOT NULL DEFAULT 0,
  reading_time_min        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  view_count              INT UNSIGNED NOT NULL DEFAULT 0,
  seo_title               VARCHAR(200) NULL,
  seo_description         VARCHAR(320) NULL,
  source_name             VARCHAR(160) NULL,
  source_url              VARCHAR(500) NULL,
  published_at            DATETIME NULL,
  scheduled_at            DATETIME NULL,
  created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_articles_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  KEY idx_articles_status (status),
  KEY idx_articles_category (category_id),
  KEY idx_articles_published (published_at),
  KEY idx_articles_featured (is_featured),
  KEY idx_articles_breaking (is_breaking),
  KEY idx_articles_hero (is_hero),
  FULLTEXT KEY ft_articles_search (title, excerpt, content_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── article_tags (many-to-many) ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS article_tags (
  article_id INT UNSIGNED NOT NULL,
  tag_id     INT UNSIGNED NOT NULL,
  PRIMARY KEY (article_id, tag_id),
  CONSTRAINT fk_article_tags_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  CONSTRAINT fk_article_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── article_views (powers "most read" time filters) ───────────────────
CREATE TABLE IF NOT EXISTS article_views (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id INT UNSIGNED NOT NULL,
  viewed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_article_views_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  KEY idx_article_views_lookup (article_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── site_settings (singleton row, id is always 1) ──────────────────────
CREATE TABLE IF NOT EXISTS site_settings (
  id                        TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  site_name                 VARCHAR(160) NOT NULL DEFAULT 'Монгол Мэдээ',
  logo_url                  VARCHAR(500) NULL,
  logo_dark_url             VARCHAR(500) NULL,
  favicon_url               VARCHAR(500) NULL,
  primary_color             VARCHAR(7) NOT NULL DEFAULT '#1d4ed8',
  contact_email             VARCHAR(190) NULL,
  contact_phone             VARCHAR(60) NULL,
  contact_address           VARCHAR(255) NULL,
  facebook_url              VARCHAR(300) NULL,
  twitter_url               VARCHAR(300) NULL,
  youtube_url               VARCHAR(300) NULL,
  instagram_url             VARCHAR(300) NULL,
  footer_description        TEXT NULL,
  seo_default_title         VARCHAR(200) NULL,
  seo_default_description   VARCHAR(320) NULL,
  analytics_id              VARCHAR(60) NULL,
  articles_per_page         TINYINT UNSIGNED NOT NULL DEFAULT 12,
  dark_mode_enabled         TINYINT(1) NOT NULL DEFAULT 1,
  maintenance_mode          TINYINT(1) NOT NULL DEFAULT 0,
  maintenance_message       TEXT NULL,
  updated_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── newsletter_subscribers ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL UNIQUE,
  subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- Seed data
-- ============================================================================

INSERT INTO site_settings (id, site_name, logo_url, logo_dark_url, primary_color, contact_email, footer_description, seo_default_title, seo_default_description, articles_per_page, dark_mode_enabled)
VALUES (1, 'Khaan.mn', '/assets/img/logo-light.png', '/assets/img/logo-dark.png', '#1d4ed8', 'info@example.com',
  'Khaan.mn — өдөр тутмын шуурхай мэдээ, нийтлэлийг хүргэдэг мэдээллийн эх сурвалж.',
  'Khaan.mn — Сүүлийн үеийн мэдээ, нийтлэл',
  'Улс төр, нийгэм, эдийн засаг, спорт, технологийн сүүлийн үеийн мэдээ, нийтлэлүүд.',
  12, 1)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO categories (id, name, slug, description, color, sort_order) VALUES
  (1, 'Улс төр',    'улс-төр',    'Улс төрийн сүүлийн үеийн мэдээ, шийдвэрүүд.', '#dc2626', 1),
  (2, 'Нийгэм',      'нийгэм',      'Нийгмийн амьдрал, хот суурин газрын мэдээ.',   '#059669', 2),
  (3, 'Эдийн засаг', 'эдийн-засаг', 'Эдийн засаг, бизнес, зах зээлийн мэдээ.',      '#d97706', 3),
  (4, 'Дэлхий',      'дэлхий',      'Дэлхий дахинаас ирсэн сонирхолтой мэдээ.',     '#2563eb', 4),
  (5, 'Технологи',   'технологи',   'Технологи, инновацийн мэдээ.',                 '#7c3aed', 5),
  (6, 'Спорт',       'спорт',       'Спортын үзвэр, тэмцээний мэдээ.',              '#0891b2', 6),
  (7, 'Соёл урлаг',  'соёл-урлаг',  'Соёл, урлаг, зугаа цэнгэлийн мэдээ.',          '#db2777', 7),
  (8, 'Эрүүл мэнд',  'эрүүл-мэнд',  'Эрүүл мэнд, анагаах ухааны мэдээ.',            '#16a34a', 8)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO tags (id, name, slug) VALUES
  (1, 'Улаанбаатар', 'улаанбаатар'),
  (2, 'цахим үйлчилгээ', 'цахим-үйлчилгээ'),
  (3, 'инноваци', 'инноваци'),
  (4, 'төсөв', 'төсөв')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Demo articles — safe to edit or delete from the admin panel at any time.
INSERT INTO articles
  (title, slug, excerpt, content, content_text, status, category_id, author_name,
   is_featured, is_breaking, is_hero, reading_time_min, view_count, published_at)
VALUES
(
  'Засгийн газрын хуралдаанаар шинэ хуулийн төслийг хэлэлцлээ',
  'засгийн-газрын-хуралдаанаар-шинэ-хуулийн-төслийг-хэлэлцлээ',
  'Өнөөдөр болсон Засгийн газрын ээлжит хуралдаанаар цахим үйлчилгээг өргөжүүлэх тухай хуулийн төслийг хэлэлцэж, holoq shine шатанд шилжүүллээ.',
  '<p>Өнөөдөр болсон Засгийн газрын ээлжит хуралдаанаар олон нийтэд чиглэсэн цахим үйлчилгээг өргөжүүлэх тухай хуулийн шинэчилсэн төслийг хэлэлцлээ.</p><p>Хуралдаанд оролцогчид төслийн талаар байр сууриа илэрхийлж, холбогдох яамдууд саналаа нэгтгэн ирэх долоо хоногт эцсийн хувилбарыг танилцуулахаар боллоо.</p><p>Хуулийн төсөл батлагдвал иргэд төрийн үйлчилгээг цахимаар авах боломж нэмэгдэнэ гэж холбогдох албан тушаалтан мэдэгдэв.</p>',
  'Өнөөдөр болсон Засгийн газрын ээлжит хуралдаанаар олон нийтэд чиглэсэн цахим үйлчилгээг өргөжүүлэх тухай хуулийн шинэчилсэн төслийг хэлэлцлээ. Хуралдаанд оролцогчид төслийн талаар байр сууриа илэрхийлж, холбогдох яамдууд саналаа нэгтгэн ирэх долоо хоногт эцсийн хувилбарыг танилцуулахаар боллоо.',
  'published', 1, 'Редакц', 1, 1, 1, 2, 1840, DATE_SUB(NOW(), INTERVAL 3 HOUR)
),
(
  'Орон нутгийн сонгуулийн бэлтгэл ажил эхэллээ',
  'орон-нутгийн-сонгуулийн-бэлтгэл-ажил-эхэллээ',
  'Аймаг, нийслэлийн сонгуулийн хороод бэлтгэл ажлаа эхлүүлж, хэсгийн байрлалыг олон нийтэд танилцуулж байна.',
  '<p>Аймаг, нийслэлийн сонгуулийн хороод бэлтгэл ажлаа эхлүүлж, санал өгөх байрны байршлыг олон нийтэд танилцуулж эхэллээ.</p><p>Сонгуулийн ерөнхий хороо санал хураалтын өдрийг баталж, иргэдэд зориулсан лавлах утасны дугаарыг нээлээ.</p>',
  'Аймаг, нийслэлийн сонгуулийн хороод бэлтгэл ажлаа эхлүүлж, санал өгөх байрны байршлыг олон нийтэд танилцуулж эхэллээ. Сонгуулийн ерөнхий хороо санал хураалтын өдрийг баталлаа.',
  'published', 1, 'Редакц', 0, 0, 0, 1, 612, DATE_SUB(NOW(), INTERVAL 1 DAY)
),
(
  'Нийслэлийн нийтийн тээврийн шинэ маршрут нэвтэрлээ',
  'нийслэлийн-нийтийн-тээврийн-шинэ-маршрут-нэвтэрлээ',
  'Түгжрэлийг бууруулах зорилгоор нийслэлд нийтийн тээврийн хоёр шинэ чиглэл нэмэгдлээ.',
  '<p>Нийслэлийн Тээврийн газраас түгжрэлийг бууруулах зорилгоор хоёр шинэ автобусны чиглэлийг нэвтрүүллээ.</p><p>Шинэ маршрутууд хотын захын дүүргүүдийг төвтэй холбох бөгөөд өглөө 6 цагаас шөнийн 11 цаг хүртэл үйлчилнэ.</p><p>Иргэд аяллын хуваарийг албан ёсны аппликейшнээс шалгах боломжтой боллоо.</p>',
  'Нийслэлийн Тээврийн газраас түгжрэлийг бууруулах зорилгоор хоёр шинэ автобусны чиглэлийг нэвтрүүллээ. Шинэ маршрутууд хотын захын дүүргүүдийг төвтэй холбоно.',
  'published', 2, 'Редакц', 1, 1, 0, 2, 2456, DATE_SUB(NOW(), INTERVAL 5 HOUR)
),
(
  'Хотын хог хаягдлын менежментийг сайжруулах төлөвлөгөө гарлаа',
  'хотын-хог-хаягдлын-менежментийг-сайжруулах-төлөвлөгөө-гарлаа',
  'Нийслэл хог хаягдлыг ангилан ялгах системийг дүүрэг бүрт нэвтрүүлэх төлөвлөгөөг баталлаа.',
  '<p>Нийслэлийн Байгаль орчны газар хог хаягдлыг эх үүсвэр дээр нь ангилан ялгах системийг дүүрэг бүрт үе шаттайгаар нэвтрүүлэхээр боллоо.</p><p>Төлөвлөгөөний дагуу иргэдэд зориулсан гарын авлага, сургалтыг дараа сараас эхлүүлнэ.</p>',
  'Нийслэлийн Байгаль орчны газар хог хаягдлыг эх үүсвэр дээр нь ангилан ялгах системийг дүүрэг бүрт үе шаттайгаар нэвтрүүлэхээр боллоо.',
  'published', 2, 'Редакц', 0, 0, 0, 1, 389, DATE_SUB(NOW(), INTERVAL 2 DAY)
),
(
  'Төгрөгийн ханш сүүлийн долоо хоногт тогтвортой байна',
  'төгрөгийн-ханш-сүүлийн-долоо-хоногт-тогтвортой-байна',
  'Гадаад валютын зах зээл дэх төгрөгийн ханш сүүлийн долоо хоногт харьцангуй тогтвортой хэвээр байна.',
  '<p>Монголбанкны мэдээллээр гадаад валютын зах зээл дэх төгрөгийн ханш сүүлийн долоо хоногт харьцангуй тогтвортой хэвээр байна.</p><p>Эдийн засагчид энэ байдал ойрын хугацаанд үргэлжлэх төлөвтэй байгааг тэмдэглэж байна.</p><p>Гадаад худалдааны нөхцөл, түүхий эдийн үнийн хөдөлгөөн ханшид нөлөөлж байгаа гол хүчин зүйл гэж дүгнэжээ.</p>',
  'Монголбанкны мэдээллээр гадаад валютын зах зээл дэх төгрөгийн ханш сүүлийн долоо хоногт харьцангуй тогтвортой хэвээр байна. Эдийн засагчид энэ байдал үргэлжлэх төлөвтэй гэж үзэж байна.',
  'published', 3, 'Редакц', 1, 0, 0, 2, 3021, DATE_SUB(NOW(), INTERVAL 8 HOUR)
),
(
  'Жижиг дунд бизнесийг дэмжих зээлийн хөтөлбөр эхэллээ',
  'жижиг-дунд-бизнесийг-дэмжих-зээлийн-хөтөлбөр-эхэллээ',
  'Жижиг, дунд үйлдвэрлэл эрхлэгчдэд зориулсан хөнгөлөлттэй зээлийн шинэ хөтөлбөрийг эхлүүллээ.',
  '<p>Санхүүгийн зохицуулах хороо жижиг, дунд үйлдвэрлэл эрхлэгчдэд зориулсан хөнгөлөлттэй зээлийн шинэ хөтөлбөрийг өнөөдрөөс эхлүүллээ.</p><p>Хөтөлбөрт хамрагдахыг хүсэгчид харьяа дүүргийн ажлын албанд хандан бүртгүүлэх боломжтой.</p>',
  'Санхүүгийн зохицуулах хороо жижиг, дунд үйлдвэрлэл эрхлэгчдэд зориулсан хөнгөлөлттэй зээлийн шинэ хөтөлбөрийг өнөөдрөөс эхлүүллээ.',
  'published', 3, 'Редакц', 0, 0, 0, 1, 745, DATE_SUB(NOW(), INTERVAL 3 DAY)
),
(
  'Олон улсын хамтын ажиллагааны чуулган зохион байгуулагдлаа',
  'олон-улсын-хамтын-ажиллагааны-чуулган-зохион-байгуулагдлаа',
  'Бүс нутгийн орнуудын төлөөлөгчид эдийн засаг, байгаль орчны чиглэлээр хамтран ажиллах хэлэлцээрт гарын үсэг зурлаа.',
  '<p>Бүс нутгийн орнуудын төлөөлөгчид оролцсон олон улсын чуулган өнөөдөр өндөрлөлөө.</p><p>Оролцогч талууд эдийн засаг, байгаль орчны чиглэлээр хамтран ажиллах санамж бичигт гарын үсэг зурав.</p><p>Дараагийн уулзалтыг ирэх онд зохион байгуулахаар тохиролцлоо.</p>',
  'Бүс нутгийн орнуудын төлөөлөгчид оролцсон олон улсын чуулган өнөөдөр өндөрлөж, эдийн засаг, байгаль орчны чиглэлээр хамтран ажиллах санамж бичигт гарын үсэг зурлаа.',
  'published', 4, 'Редакц', 0, 0, 0, 2, 528, DATE_SUB(NOW(), INTERVAL 4 DAY)
),
(
  'Хиймэл оюун ухааны шинэ платформ монгол хэлийг дэмжиж эхэллээ',
  'хиймэл-оюун-ухааны-шинэ-платформ-монгол-хэлийг-дэмжиж-эхэллээ',
  'Дэлхийн тэргүүлэх технологийн компаниудын нэг хиймэл оюун ухааны үйлчилгээндээ монгол хэлний дэмжлэгийг нэмлээ.',
  '<p>Дэлхийн тэргүүлэх технологийн компаниудын нэг хиймэл оюун ухааны үйлчилгээндээ монгол хэлний дэмжлэгийг албан ёсоор нэмлээ.</p><p>Энэ нь дотоодын хэрэглэгчид, хөгжүүлэгчдэд шинэ боломж нээж өгнө гэж мэргэжилтнүүд тэмдэглэж байна.</p><p>Компани цаашид орон нутгийн платформуудтай хамтран ажиллах төлөвлөгөөтэй байгаагаа мэдэгдэв.</p>',
  'Дэлхийн тэргүүлэх технологийн компаниудын нэг хиймэл оюун ухааны үйлчилгээндээ монгол хэлний дэмжлэгийг албан ёсоор нэмлээ. Энэ нь дотоодын хэрэглэгчид, хөгжүүлэгчдэд шинэ боломж нээж өгнө.',
  'published', 5, 'Редакц', 1, 0, 0, 2, 4210, DATE_SUB(NOW(), INTERVAL 6 HOUR)
),
(
  'Улаанбаатар хотод цахим засаглалын шинэ үйлчилгээ нэвтэрлээ',
  'улаанбаатар-хотод-цахим-засаглалын-шинэ-үйлчилгээ-нэвтэрлээ',
  'Иргэд танилцуулга авахын тулд байгууллагад биечлэн очих шаардлагагүй болох цахим үйлчилгээ нээлтээ хийлээ.',
  '<p>Нийслэлийн Засаг даргын Тамгын газраас иргэдэд зориулсан шинэ цахим үйлчилгээний системийг нээлээ.</p><p>Системээр дамжуулан иргэд байгууллагад биечлэн очихгүйгээр лавлагаа, тодорхойлолт авах боломжтой болно.</p>',
  'Нийслэлийн Засаг даргын Тамгын газраас иргэдэд зориулсан шинэ цахим үйлчилгээний системийг нээлээ. Иргэд байгууллагад биечлэн очихгүйгээр лавлагаа авах боломжтой болно.',
  'published', 5, 'Редакц', 0, 1, 0, 1, 1673, DATE_SUB(NOW(), INTERVAL 10 HOUR)
),
(
  'Үндэсний шигшээ баг ирэх улирлын бэлтгэлээ эхлүүлэв',
  'үндэсний-шигшээ-баг-ирэх-улирлын-бэлтгэлээ-эхлүүлэв',
  'Шигшээ багийн ахлах дасгалжуулагч ирэх улирлын тэмцээний бэлтгэл, тоглогчдын нэрсийн урьдчилсан жагсаалтыг танилцууллаа.',
  '<p>Үндэсний шигшээ багийн ахлах дасгалжуулагч өнөөдөр хэвлэлийн бага хурал зарлаж, ирэх улирлын тэмцээний бэлтгэлийн талаар мэдээлэл өглөө.</p><p>Тоглогчдын урьдчилсан нэрсийн жагсаалтыг танилцуулж, бэлтгэл хугацаагаа дараа долоо хоногоос эхлүүлнэ гэдгийг тэмдэглэв.</p>',
  'Үндэсний шигшээ багийн ахлах дасгалжуулагч өнөөдөр хэвлэлийн бага хурал зарлаж, ирэх улирлын тэмцээний бэлтгэлийн талаар мэдээлэл өглөө.',
  'published', 6, 'Редакц', 0, 0, 0, 1, 967, DATE_SUB(NOW(), INTERVAL 1 DAY)
),
(
  'Дотоодын лигийн улирлын аварга шалгарлаа',
  'дотоодын-лигийн-улирлын-аварга-шалгарлаа',
  'Улирлын турш үргэлжилсэн дотоодын лигийн аварга шалгаруулах тоглолт өнгөрсөн амралтын өдрүүдэд болж өндөрлөлөө.',
  '<p>Улирлын турш үргэлжилсэн дотоодын лигийн аварга шалгаруулах шигшээ тоглолт амжилттай болж өндөрлөлөө.</p><p>Тэргүүлсэн баг өмнөх онтой харьцуулахад илүү тогтвортой тоглож, аваргын өргөмжлөлийг гардан авлаа.</p>',
  'Улирлын турш үргэлжилсэн дотоодын лигийн аварга шалгаруулах шигшээ тоглолт амжилттай болж, тэргүүлсэн баг аваргын өргөмжлөлийг гардан авлаа.',
  'published', 6, 'Редакц', 0, 0, 0, 1, 1204, DATE_SUB(NOW(), INTERVAL 2 DAY)
),
(
  'Утга зохиол, урлагийн наадам долоо хоногийн турш үргэлжилнэ',
  'утга-зохиол-урлагийн-наадам-долоо-хоногийн-турш-үргэлжилнэ',
  'Уран бүтээлчид, зохиолчдыг нэгтгэсэн жил бүрийн уламжлалт наадам өнөөдрөөс нээлтээ хийлээ.',
  '<p>Уран бүтээлчид, зохиолчдыг нэгтгэсэн жил бүрийн уламжлалт наадам өнөөдрөөс нээлтээ хийж, долоо хоногийн турш үргэлжилнэ.</p><p>Наадмын хүрээнд ном хэвлэлийн яармаг, уран уншлага, дүрслэх урлагийн үзэсгэлэн зэрэг арга хэмжээ зохион байгуулагдана.</p><p>Оролцогчид үнэ төлбөргүйгээр үзэсгэлэнд зочилж болно.</p>',
  'Уран бүтээлчид, зохиолчдыг нэгтгэсэн жил бүрийн уламжлалт наадам өнөөдрөөс нээлтээ хийж, долоо хоногийн турш үргэлжилнэ. Наадмын хүрээнд ном хэвлэлийн яармаг, уран уншлага зохион байгуулагдана.',
  'published', 7, 'Редакц', 1, 0, 0, 2, 856, DATE_SUB(NOW(), INTERVAL 12 HOUR)
),
(
  'Өвлийн улиралд зориулсан вакцинжуулалтын кампанит ажил эхэллээ',
  'өвлийн-улиралд-зориулсан-вакцинжуулалтын-кампанит-ажил-эхэллээ',
  'Эрүүл мэндийн байгууллагууд өвлийн улиралд зориулсан улирлын вакцинжуулалтын үнэ төлбөргүй кампанит ажлыг эхлүүллээ.',
  '<p>Эрүүл мэндийн яамнаас өвлийн улиралд зориулсан улирлын вакцинжуулалтын кампанит ажлыг өнөөдрөөс эхлүүллээ.</p><p>Кампанит ажил эрсдэлт бүлгийн иргэдэд зориулагдах бөгөөд дүүрэг, сумын эрүүл мэндийн төвүүдэд үнэ төлбөргүй үйлчилнэ.</p>',
  'Эрүүл мэндийн яамнаас өвлийн улиралд зориулсан улирлын вакцинжуулалтын кампанит ажлыг өнөөдрөөс эхлүүллээ. Кампанит ажил эрсдэлт бүлгийн иргэдэд зориулагдана.',
  'published', 8, 'Редакц', 0, 1, 0, 1, 1389, DATE_SUB(NOW(), INTERVAL 7 HOUR)
),
(
  'Гэр бүлийн эмч нарын тоог нэмэгдүүлэх хөтөлбөр батлагдлаа',
  'гэр-бүлийн-эмч-нарын-тоог-нэмэгдүүлэх-хөтөлбөр-батлагдлаа',
  'Хөдөө орон нутагт гэр бүлийн эмч нарын хүрэлцээг сайжруулах дунд хугацааны хөтөлбөрийг баталлаа.',
  '<p>Эрүүл мэндийн яам хөдөө орон нутагт гэр бүлийн эмч нарын хүрэлцээг сайжруулах дунд хугацааны хөтөлбөрийг баталлаа.</p><p>Хөтөлбөрийн хүрээнд залуу эмч нарыг орон нутагт ажиллуулах урамшууллын тогтолцоог нэвтрүүлнэ.</p>',
  'Эрүүл мэндийн яам хөдөө орон нутагт гэр бүлийн эмч нарын хүрэлцээг сайжруулах дунд хугацааны хөтөлбөрийг баталлаа.',
  'published', 8, 'Редакц', 0, 0, 0, 1, 421, DATE_SUB(NOW(), INTERVAL 5 DAY)
),
(
  'Ирэх сарын хотын баяр наадмын хөтөлбөр гарлаа',
  'ирэх-сарын-хотын-баяр-наадмын-хөтөлбөр-гарлаа',
  'Ирэх сард болох хотын уламжлалт баяр наадмын дэлгэрэнгүй хөтөлбөрийг олон нийтэд танилцууллаа.',
  '<p>Нийслэлийн соёл, урлагийн газраас ирэх сард болох хотын уламжлалт баяр наадмын дэлгэрэнгүй хөтөлбөрийг өнөөдөр танилцууллаа.</p><p>Наадмын хүрээнд гудамжны тоглолт, гар урлалын яармаг, хүүхдэд зориулсан хөтөлбөр зохион байгуулагдана.</p>',
  'Нийслэлийн соёл, урлагийн газраас ирэх сард болох хотын уламжлалт баяр наадмын дэлгэрэнгүй хөтөлбөрийг өнөөдөр танилцууллаа.',
  'draft', 7, 'Редакц', 0, 0, 0, 1, 0, NULL
);

-- Tag a few demo articles so the tag feature has something to show.
INSERT INTO article_tags (article_id, tag_id) VALUES
  (3, 1), (3, 2), (9, 2), (9, 3), (10, 1);

-- Backfill some demo view history so the "most read" time-filter tabs
-- (today / this week / all-time) have something meaningful to rank.
INSERT INTO article_views (article_id, viewed_at)
SELECT a.id, DATE_SUB(NOW(), INTERVAL (n.n * 37) MINUTE)
FROM articles a
JOIN (
  SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) n
WHERE a.status = 'published'
  AND n.n <= FLOOR(a.view_count / 300) + 1;
