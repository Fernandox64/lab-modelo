<style>
.lab-quick-menu{position:fixed;top:9rem;right:1rem;width:238px;z-index:1100;background:linear-gradient(180deg,#ffffff,#f5f9ff);border:1px solid rgba(84,120,255,.3);border-radius:14px;box-shadow:0 12px 28px rgba(17,31,162,.18);padding:12px}
.lab-quick-menu h3{margin:0 0 10px 0;font-size:1.02rem;font-weight:700;color:var(--decom-navy)}
.lab-quick-menu a{display:block;text-align:center;text-decoration:none;font-weight:700;font-size:.94rem;padding:9px 10px;margin-bottom:8px;border-radius:8px;border:1px solid var(--decom-blue);background:#fff;color:var(--decom-blue);transition:all .18s ease}
.lab-quick-menu a:first-of-type{background:var(--decom-blue);color:#fff;border-color:var(--decom-blue)}
.lab-quick-menu a:hover,.lab-quick-menu a:focus{background:var(--decom-cyan);color:var(--decom-navy);border-color:var(--decom-cyan)}
@media (max-width:1200px){.lab-quick-menu{display:none}}

.scroll-top-progress{--progress:0%;position:fixed;right:1rem;bottom:1rem;width:56px;height:56px;border:0;border-radius:50%;padding:3px;cursor:pointer;z-index:1120;background:conic-gradient(var(--decom-blue) var(--progress),rgba(84,120,255,.2) 0);box-shadow:0 8px 18px rgba(17,31,162,.2);opacity:0;visibility:hidden;transform:translateY(10px);transition:opacity .2s ease,transform .2s ease,visibility .2s ease}
.scroll-top-progress.is-visible{opacity:1;visibility:visible;transform:translateY(0)}
.scroll-top-progress span{display:grid;place-items:center;width:100%;height:100%;border-radius:50%;background:#fff;color:var(--decom-navy);font-size:1.15rem;font-weight:700;line-height:1}
.scroll-top-progress span i{font-size:1.05rem;line-height:1}
.scroll-top-progress:hover span,.scroll-top-progress:focus span{background:var(--decom-cyan);color:var(--decom-navy)}
</style>
<aside class="lab-quick-menu" aria-label="Menu de acesso rapido">
    <h3>Acesso Rapido</h3>
    <a href="/">Home</a>
    <a href="/laboratorio/equipe.php">Equipe</a>
    <a href="/laboratorio/projetos.php">Projetos</a>
    <a href="/laboratorio/publicacoes.php">Publicacoes</a>
    <a href="/laboratorio/cursos.php">Cursos</a>
    <a href="/laboratorio/parceiros.php">Parceiros</a>
    <a href="/laboratorio/tutoriais.php">Tutoriais</a>
    <a href="/laboratorio/blog.php">Blog</a>
    <a href="/laboratorio/eventos.php">Eventos</a>
</aside>
<button id="scrollTopProgress" class="scroll-top-progress" type="button" aria-label="Voltar ao topo">
    <span><i class="bi bi-arrow-up" aria-hidden="true"></i></span>
</button>
<footer class="border-top bg-white mt-5 py-4"><div class="container d-flex justify-content-between flex-wrap gap-2"><div><strong><?= e(SITE_NAME) ?> (<?= e(SITE_SIGLA) ?>)</strong><br><span class="text-muted"><?= e(SITE_UNIVERSITY) ?></span></div><div class="text-muted text-end"><?= e(SITE_PHONE) ?><br><?= e(SITE_EMAIL) ?></div></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var btn = document.getElementById('scrollTopProgress');
    if (!btn) return;

    function updateScrollProgress() {
        var top = window.scrollY || document.documentElement.scrollTop || 0;
        var total = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
        var pct = total > 0 ? Math.min(100, Math.max(0, (top / total) * 100)) : 0;
        btn.style.setProperty('--progress', pct + '%');
        btn.setAttribute('aria-label', 'Voltar ao topo (' + Math.round(pct) + '%)');
        if (top > 180) {
            btn.classList.add('is-visible');
        } else {
            btn.classList.remove('is-visible');
        }
    }

    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', updateScrollProgress, { passive: true });
    window.addEventListener('resize', updateScrollProgress);
    updateScrollProgress();
})();
</script>
</body></html>
