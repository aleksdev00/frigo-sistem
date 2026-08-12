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
