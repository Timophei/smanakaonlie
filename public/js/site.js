// «Показать телефон»: нажатие уходит на сервер (там оно считается), в ответ приходит номер.
document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-phone-url]');
  if (!button || button.dataset.done) return;
  event.preventDefault();
  button.disabled = true;
  try {
    const response = await fetch(button.dataset.phoneUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': button.dataset.token, Accept: 'application/json' },
    });
    if (!response.ok) throw new Error(response.status);
    const { phone } = await response.json();
    const link = document.createElement('a');
    link.className = button.className;
    link.href = 'tel:' + phone.replace(/[^\d+]/g, '');
    link.textContent = phone;
    button.replaceWith(link);
  } catch (error) {
    button.disabled = false;
    button.querySelector('[data-label]').textContent = 'Не получилось, попробуйте ещё раз';
  }
});
