// Troque os arquivos em /assets/images sem alterar templates.
// Enquanto não houver imagem local, utiliza a foto de demonstração hospedada externamente.
window.RestaurantMedia={
hero:'/assets/images/hero.jpg',
ambience:'/assets/images/ambience.jpg',
reservation:'/assets/images/reservation.jpg',
fallbacks:{
hero:'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1800&q=85',
ambience:'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=85',
reservation:'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=85',
dish1:'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=850&q=80',
dish2:'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=850&q=80',
dish3:'https://images.unsplash.com/photo-1476124369491-e7addf5db371?auto=format&fit=crop&w=850&q=80',
dish4:'https://images.unsplash.com/photo-1513558161293-cdaf765edfd4?auto=format&fit=crop&w=850&q=80'
}};
window.setRestaurantImage=function(img,local,fallback){
 img.src=local;img.onerror=()=>{img.onerror=null;img.src=fallback;};
};
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('[data-media]').forEach(img=>{const k=img.dataset.media;setRestaurantImage(img,RestaurantMedia[k],RestaurantMedia.fallbacks[k]);});});
