<?php
function view(string $path, array $data = []): void {
  extract($data, EXTR_SKIP);
  require __DIR__ . '/../' . ltrim($path, '/');
}

function partial(string $name, array $data = []): void {
  view('components/' . ltrim($name, '/'), $data);
}
