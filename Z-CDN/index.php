<?php
// Configuration: Folder to scan on the server
$directory = './'; 
$filesOnServer = [];

if (is_dir($directory)) {
    if ($dh = opendir($directory)) {
        while (($file = readdir($dh)) !== false) {
            if ($file != "." && $file != "..") {
                $filesOnServer[] = [
                    "name" => $file,
                    "size" => filesize($directory . $file),
                    "isDir" => is_dir($directory . $file)
                ];
            }
        }
        closedir($dh);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Asset Downloader & Server Explorer</title>
<style>
body { font-family: sans-serif; padding: 20px; line-height: 1.5; background: #f4f4f9; }
.container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
#explorer { margin-top: 20px; border: 1px solid #ddd; border-radius: 4px; background: #fff; }
.explorer-header { background: #eee; padding: 10px; font-weight: bold; border-bottom: 1px solid #ddd; }
.file-item { padding: 8px 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; font-size: 14px; }
.file-item:last-child { border-bottom: none; }
#log { margin-top: 20px; padding: 15px; border: 1px solid #ddd; background: #222; color: #eee; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; border-radius: 4px; }
.success { color: #aaffaa; }
.error { color: #ffaaaa; }
button { padding: 10px 20px; cursor: pointer; background: #0078d4; color: white; border: none; border-radius: 4px; font-weight: bold; }
button:disabled { background: #ccc; }
</style>
</head>
<body>
<div class="container">
<h2>Monaco Editor Downloader</h2>
<p>Target Path: <code>dev/vs/</code></p>
<button id="downloadBtn">Start Download</button>

<div id="explorer">
<div class="explorer-header">Files Currently on Server</div>
<div id="fileList">
<?php if (empty($filesOnServer)): ?>
<div class="file-item">No files found.</div>
<?php else: ?>
<?php foreach ($filesOnServer as $f): ?>
<div class="file-item">
<span><?php echo $f['isDir'] ? '📁' : '📄'; ?> <?php echo htmlspecialchars($f['name']); ?></span>
<span><?php echo number_format($f['size'] / 1024, 2); ?> KB</span>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>

<div id="log"></div>
</div>

<script>
const package = "monaco-editor@0.56.0-dev-20260211";
const targetPath = "dev/vs/";
const api = `https://data.jsdelivr.com/v1/package/npm/${package}/flat`;
const cdnBase = `https://cdn.jsdelivr.net/npm/${package}`; 

const logElement = document.getElementById('log');
const btn = document.getElementById('downloadBtn');

function log(msg, type = 'info') {
const div = document.createElement('div');
div.className = type;
div.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
logElement.appendChild(div);
logElement.scrollTop = logElement.scrollHeight;
}

async function downloadFile(url, fileName) {
try {
const response = await fetch(url);
if (!response.ok) throw new Error(`HTTP ${response.status}`);
const blob = await response.blob();
const link = document.createElement('a');
link.href = URL.createObjectURL(blob);
link.download = fileName;
document.body.appendChild(link);
link.click();
document.body.removeChild(link);
log(`✓ Saved: ${fileName}`, 'success');
} catch (err) {
log(`✗ Error downloading ${fileName}: ${err.message}`, 'error');
}
}

btn.addEventListener('click', async () => {
btn.disabled = true;
logElement.innerHTML = '';
log("Connecting to jsDelivr API...");
try {
const res = await fetch(api);
const data = await res.json();
const cleanTarget = targetPath.replace(/^\/|\/$/g, '');
const files = data.files.filter(f => {
const cleanName = f.name.replace(/^\//, '');
return cleanName.startsWith(cleanTarget) && cleanName.endsWith('.css');
});
log(`Found ${files.length} matching files.`);
for (const file of files) {
const fileName = file.name.split('/').pop();
const fullUrl = cdnBase + (file.name.startsWith('/') ? '' : '/') + file.name;
await downloadFile(fullUrl, fileName);
await new Promise(r => setTimeout(r, 250));
}
log("Process finished. Refresh page to update server file list.");
} catch (err) {
log(`Critical Error: ${err.message}`, 'error');
} finally {
btn.disabled = false;
}
});
</script>
</body>
</html>