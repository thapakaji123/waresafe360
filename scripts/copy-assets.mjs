import { copyFile, mkdir } from 'node:fs/promises';
import { join } from 'node:path';

const destination = join(process.cwd(), 'public', 'assets', 'vendor');
await mkdir(destination, { recursive: true });

const assets = [
  ['node_modules/bootstrap/dist/css/bootstrap.min.css', 'bootstrap.min.css'],
  ['node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'bootstrap.bundle.min.js'],
  ['node_modules/chart.js/dist/chart.umd.js', 'chart.umd.js'],
  ['node_modules/marzipano/dist/marzipano.js', 'marzipano.js'],
];

for (const [source, name] of assets) {
  await copyFile(join(process.cwd(), source), join(destination, name));
}

console.log(`Copied ${assets.length} browser assets to public/assets/vendor.`);

