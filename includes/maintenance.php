<!doctype html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($settings['site_name'] ?? 'Мэдээний сайт') ?> — Түр засвартай байна</title>
<style>
  :root { color-scheme: light dark; }
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    font-family: 'Noto Sans', system-ui, sans-serif; background: #0b0d12; color: #eef1f6;
    text-align: center; padding: 2rem;
  }
  .box { max-width: 32rem; }
  h1 { font-size: 1.5rem; margin-bottom: .75rem; }
  p { color: #9aa3b2; line-height: 1.6; }
</style>
</head>
<body>
  <div class="box">
    <h1><?= e($settings['site_name'] ?? 'Мэдээний сайт') ?></h1>
    <p><?= nl2br(e($settings['maintenance_message'] ?? 'Сайт түр засвар үйлчилгээнд орсон байна. Түр хүлээнэ үү.')) ?></p>
  </div>
</body>
</html>
