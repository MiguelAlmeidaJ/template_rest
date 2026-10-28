document.addEventListener('DOMContentLoaded', () => {
  const list=document.querySelector('.notification-list');
  if (!list) return;
  let loading=false;
  async function refresh(){
    if (loading||document.hidden) return;
    loading=true;
    try{
      const response=await fetch('/admin/notifications.php',{credentials:'same-origin',cache:'no-store'});
      if(!response.ok) return;
      const doc=new DOMParser().parseFromString(await response.text(),'text/html');
      const replacement=doc.querySelector('.notification-list');
      if(!replacement)return;
      const oldIds=[...list.querySelectorAll('[name=id]')].map(el=>el.value).join(',');
      const newIds=[...replacement.querySelectorAll('[name=id]')].map(el=>el.value).join(',');
      if(oldIds!==newIds||list.children.length!==replacement.children.length){
        list.replaceChildren(...[...replacement.children]);
      }
      const newCount=doc.querySelector('.content-heading .text-secondary');
      const count=document.querySelector('.content-heading .text-secondary');
      if(newCount&&count)count.textContent=newCount.textContent;
    }catch(error){/* Keep the current list on temporary connection errors. */}
    finally{loading=false}
  }
  const timer=setInterval(refresh,10000);
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh()});
  window.addEventListener('pagehide',()=>clearInterval(timer),{once:true});
});