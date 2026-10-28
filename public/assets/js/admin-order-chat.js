document.addEventListener('DOMContentLoaded', () => {
  const list=document.querySelector('#order-chat-messages');
  const form=document.querySelector('#order-chat-form');
  if (!list||!form) return;
  const id=list.dataset.orderId;
  const feedback=document.querySelector('#order-chat-feedback');
  let lastSignature='';
  let busy=false;
  function render(messages) {
    const signature=messages.map(m=>m.id).join(',');
    if (signature===lastSignature) return;
    lastSignature=signature;
    const nearBottom=list.scrollHeight-list.scrollTop-list.clientHeight<90;
    const nodes=messages.map(message=>{
      const item=document.createElement('article');item.className='order-chat-message '+(message.sender==='restaurant'?'from-staff':'from-client');
      const name=document.createElement('strong');name.textContent=message.sender==='restaurant'?'Restaurante':'Cliente';
      const body=document.createElement('p');body.textContent=message.body;
      const date=document.createElement('small');date.textContent=message.created_at;
      item.append(name,body,date);return item;
    });
    list.replaceChildren(...(nodes.length?nodes:[Object.assign(document.createElement('p'),{className:'text-secondary',textContent:'Nenhuma mensagem neste pedido.'})]));
    if (nearBottom) list.scrollTop=list.scrollHeight;
  }
  async function load(method='GET',data=null) {
    const options={method,credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}};
    if (data) options.body=data;
    const r=await fetch('/admin/order-feed.php?type=chat&order='+encodeURIComponent(id),options);
    const result=await r.json();
    if (!r.ok||!result.ok) throw Error(result.error||'Não foi possível atualizar a conversa.');
    render(result.messages||[]);
  }
  form.addEventListener('submit',async event=>{
    event.preventDefault();if(busy)return;
    busy=true;
    const button=form.querySelector('button[type=submit]');button.disabled=true;
    feedback.textContent='Enviando...';
    try {await load('POST',new FormData(form));form.elements.body.value='';feedback.textContent='Mensagem enviada.';}
    catch(error){feedback.textContent=error.message;}
    finally {busy=false;button.disabled=false;}
  });
  async function refresh(){if(busy||document.hidden)return;try{await load();}catch(error){feedback.textContent='Sem conexão. Tentaremos novamente.';}}
  refresh();
  const timer=setInterval(refresh,7000);
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh()});
  window.addEventListener('pagehide',()=>clearInterval(timer),{once:true});
});
