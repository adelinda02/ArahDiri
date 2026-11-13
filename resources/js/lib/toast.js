// Toast v14: satu elemen #toast, tampil 2,2 detik.
let timer;
export function toast(msg) {
    const t = document.getElementById('toast');
    if (!t || !msg) return;
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(timer);
    timer = setTimeout(() => t.classList.remove('show'), 2200);
}
