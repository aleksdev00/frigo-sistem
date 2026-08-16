document.addEventListener('submit', (event) => {
    const message = event.submitter?.dataset.confirm || event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('change', (event) => {
    if (event.target.matches('input[name="images[]"]')) {
        event.target.closest('form').querySelector('[data-file-count]').textContent = event.target.files.length ? `${event.target.files.length} izabrano` : 'Nijedna slika nije izabrana.';
    }
});

const normalizeSpecOrder = (list) => [...list.querySelectorAll('[data-spec-row]')].forEach((row,index) => { row.querySelector('input[name="spec_order[]"]').value=index; });
document.addEventListener('click', (event) => {
    const imageMove=event.target.closest('[data-move]');
    if(imageMove){const item=imageMove.closest('[data-image-item]');const sibling=imageMove.dataset.move==='up'?item.previousElementSibling:item.nextElementSibling;if(sibling)item.parentNode.insertBefore(imageMove.dataset.move==='up'?item:sibling,imageMove.dataset.move==='up'?sibling:item);return;}
    const form=event.target.closest('[data-spec-form]');if(!form)return;const list=form.querySelector('[data-spec-list]');
    if(event.target.closest('[data-spec-add]')){list.append(document.querySelector('[data-spec-template]').content.cloneNode(true));normalizeSpecOrder(list);return;}
    const row=event.target.closest('[data-spec-row]');if(!row)return;
    if(event.target.closest('[data-spec-remove]'))row.remove();
    const move=event.target.closest('[data-spec-move]');if(move){const sibling=move.dataset.specMove==='up'?row.previousElementSibling:row.nextElementSibling;if(sibling)row.parentNode.insertBefore(move.dataset.specMove==='up'?row:sibling,move.dataset.specMove==='up'?sibling:row);}
    normalizeSpecOrder(list);
});
const analyticsData = document.getElementById('views-chart-data');
const analyticsCanvas = document.getElementById('views-chart');
if (analyticsData && analyticsCanvas) {
    const data = JSON.parse(analyticsData.textContent || '{"labels":[],"values":[]}');
    const drawChart = () => {
        if (data.values.length === 0 || !data.values.some(value => value > 0)) return;
        const ratio = window.devicePixelRatio || 1;
        const width = analyticsCanvas.clientWidth;
        const height = analyticsCanvas.clientHeight;
        analyticsCanvas.width = width * ratio;
        analyticsCanvas.height = height * ratio;
        const context = analyticsCanvas.getContext('2d');
        context.scale(ratio, ratio);
        context.clearRect(0, 0, width, height);
        const padding = {top: 15, right: 15, bottom: 42, left: 42};
        const plotWidth = width - padding.left - padding.right;
        const plotHeight = height - padding.top - padding.bottom;
        const maximum = Math.max(1, ...data.values);
        context.strokeStyle = '#dbe5ee'; context.fillStyle = '#526b80'; context.font = '12px system-ui';
        for (let line = 0; line <= 4; line++) {
            const y = padding.top + plotHeight * line / 4;
            context.beginPath(); context.moveTo(padding.left, y); context.lineTo(width - padding.right, y); context.stroke();
            context.fillText(String(Math.round(maximum * (4 - line) / 4)), 4, y + 4);
        }
        const gap = plotWidth / Math.max(1, data.values.length);
        context.fillStyle = '#0a5ea8';
        data.values.forEach((value, index) => {
            const barHeight = plotHeight * value / maximum;
            context.fillRect(padding.left + index * gap + 1, padding.top + plotHeight - barHeight, Math.max(1, gap - 2), barHeight);
        });
        context.fillStyle = '#526b80';
        const labelIndexes = [...new Set([0, Math.floor((data.labels.length - 1) / 2), data.labels.length - 1])];
        labelIndexes.forEach(index => { if (index >= 0) context.fillText(data.labels[index], Math.min(width - 75, padding.left + index * gap), height - 12); });
    };
    drawChart();
    window.addEventListener('resize', drawChart);
}
