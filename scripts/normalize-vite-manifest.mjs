import fs from 'node:fs';
import path from 'node:path';

const manifestPath = path.join(process.cwd(), 'public', 'build', 'manifest.json');

if (!fs.existsSync(manifestPath)) {
  process.exit(0);
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const normalized = {};

for (const [key, value] of Object.entries(manifest)) {
  const canonical = key.replaceAll('\\', '/');
  let nextKey = key;

  for (const entry of ['resources/css/app.css', 'resources/js/app.js']) {
    if (canonical.endsWith(entry)) {
      nextKey = entry;
      break;
    }
  }

  normalized[nextKey] = {
    ...value,
    src: nextKey.startsWith('resources/') ? nextKey : value.src,
  };
}

fs.writeFileSync(manifestPath, `${JSON.stringify(normalized, null, 2)}\n`);
