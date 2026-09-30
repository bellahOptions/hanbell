<?php

/**
 * Temporary diagnostic: renders the storefront HTML and injects a probe that
 * reports which elements exceed the viewport width.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Livewire\Storefront\Home;
use App\Support\Seo;

$component = new Home;
$view = $component->render();

$html = $view->render();

// Inject a probe just before </body>.
$probe = <<<'JS'
<script>
(function () {
  function report() {
    var vw = document.documentElement.clientWidth;
    var out = [];
    out.push('viewport=' + vw + ' scrollWidth=' + document.documentElement.scrollWidth);

    var all = document.querySelectorAll('*');
    for (var i = 0; i < all.length; i++) {
      var el = all[i];
      var r = el.getBoundingClientRect();
      if (r.right > vw + 1 || r.left < -1) {
        var id = el.tagName.toLowerCase()
          + (el.id ? '#' + el.id : '')
          + (el.className && typeof el.className === 'string'
              ? '.' + el.className.trim().split(/\s+/).slice(0, 4).join('.')
              : '');
        out.push('OVERFLOW ' + id + ' left=' + Math.round(r.left) + ' right=' + Math.round(r.right) + ' w=' + Math.round(r.width));
      }
    }

    var pre = document.createElement('pre');
    pre.id = 'overflow-report';
    pre.textContent = out.slice(0, 40).join('\n');
    document.body.insertBefore(pre, document.body.firstChild);
  }

  window.addEventListener('load', report);
  setTimeout(report, 1500);
})();
</script>
JS;

$html = str_replace('</body>', $probe.'</body>', $html);

$path = storage_path('app/ui-review/overflow-probe.html');
@mkdir(dirname($path), 0755, true);
file_put_contents($path, $html);

echo 'written: '.$path.' ('.number_format(strlen($html))." bytes)\n";
