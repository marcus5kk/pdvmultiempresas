<footer class="bg-light text-center text-muted py-3 mt-5 no-print">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-md-start">
                <p class="mb-0">
                    &copy; <?php echo date('Y'); ?> Sistema PDV. 
                    <small>Versão 1.0</small>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">
                    <small>
                        Desenvolvido com 
                        <i class="fas fa-heart text-danger"></i> 
                        usando PHP e PostgreSQL
                    </small>
                </p>
            </div>
        </div>
        
        <div class="row mt-2">
            <div class="col-12">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Sistema de Ponto de Venda - 
                    <span id="current-time"></span>
                </small>
            </div>
        </div>
    </div>
</footer>

<script>
// Update current time in footer
function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleString('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// Update time immediately and then every second
updateCurrentTime();
setInterval(updateCurrentTime, 1000);
</script>
