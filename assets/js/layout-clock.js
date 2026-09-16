function updateClock() {
  const el = document.getElementById('clock');
  if (el) el.textContent = new Date().toLocaleTimeString();
}

setInterval(updateClock, 1000);
updateClock();
