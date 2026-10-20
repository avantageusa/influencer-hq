// Exact-asset review adapter. Calibrate another avatar before reusing a matrix.
// Does not create a session, request provider access, play audio, or change a coach.
export function createSamiBanner({canvas,video,poster,calibration,avatarId,showDisclosure=true,preserveVideoCorner=true}) {
 if(!canvas?.getContext||!video||!poster?.naturalWidth)throw Error('Loaded canvas, video and approved poster required');
 if(avatarId!==calibration.avatar_id)throw Error('Avatar calibration mismatch');
 const {matrix,output_width:width,output_height:height,video_width:vw,video_height:vh}=calibration;
 if(!Array.isArray(matrix)||matrix.length!==2||matrix.some(r=>!Array.isArray(r)||r.length!==3||r.some(n=>!Number.isFinite(n))))throw Error('Invalid calibration');
 const [a,b,tx]=matrix[0],[c,d,ty]=matrix[1];
 if(a*d-b*c<=0||width!==1152||height!==768||vw!==1152||vh!==768)throw Error('Unsupported calibration');
 canvas.width=width;canvas.height=height;canvas.setAttribute('role','img');canvas.setAttribute('aria-label','Sami, an AI avatar');
 const ctx=canvas.getContext('2d'),layer=document.createElement('canvas');layer.width=vw;layer.height=vh;const lc=layer.getContext('2d');
 let stopped=false,handle=null,frames=0,dimensionMismatch=false;
 function draw(){
  if(stopped)return;
  dimensionMismatch=Boolean(video.videoWidth&&(video.videoWidth!==vw||video.videoHeight!==vh));
  // Changing src invalidates the media frame. Keep the entire last composition
  // until the next decoded, correctly sized frame is ready. Only reset() or
  // destroy() intentionally returns to the poster.
  if(frames&&(video.readyState<2||!video.videoWidth||dimensionMismatch)){
   canvas.dataset.status=dimensionMismatch?'wrong-video-dimensions':'holding';return;
  }
  ctx.setTransform(1,0,0,1,0,0);ctx.drawImage(poster,0,0,width,height);
  if(video.readyState>=2&&video.videoWidth){
   dimensionMismatch=video.videoWidth!==vw||video.videoHeight!==vh;
   if(!dimensionMismatch){
    lc.globalCompositeOperation='source-over';lc.clearRect(0,0,vw,vh);lc.drawImage(video,0,0,vw,vh);
    // Blend only the native frame edges. The face remains opaque and animated.
    const horizontal=lc.createLinearGradient(0,0,vw,0);horizontal.addColorStop(0,'transparent');horizontal.addColorStop(24/vw,'black');horizontal.addColorStop(1-24/vw,'black');horizontal.addColorStop(1,'transparent');
    lc.globalCompositeOperation='destination-in';lc.fillStyle=horizontal;lc.fillRect(0,0,vw,vh);
    const vertical=lc.createLinearGradient(0,0,0,vh);vertical.addColorStop(0,'transparent');vertical.addColorStop(24/vh,'black');vertical.addColorStop(1-24/vh,'black');vertical.addColorStop(1,'transparent');lc.fillStyle=vertical;lc.fillRect(0,0,vw,vh);
    ctx.setTransform(a,c,b,d,tx,ty);ctx.drawImage(layer,0,0);
    // Preserve the renderer disclosure in full opacity at its original placement.
    ctx.setTransform(1,0,0,1,0,0);
    if(preserveVideoCorner)ctx.drawImage(video,vw-150,vh-65,150,65,vw-150,vh-65,150,65);
    ctx.setTransform(1,0,0,1,0,0);frames++;
   }
  }
  if(showDisclosure){ctx.font='600 17px system-ui';ctx.textAlign='right';ctx.fillStyle='rgba(0,0,0,.65)';ctx.fillRect(width-119,height-37,109,27);ctx.fillStyle='#fff';ctx.fillText('AI avatar',width-21,height-17);}
  canvas.dataset.status=dimensionMismatch?'wrong-video-dimensions':frames?'playing':'poster';
 }
 function next(){draw();if(!stopped)handle=typeof video.requestVideoFrameCallback==='function'?video.requestVideoFrameCallback(next):requestAnimationFrame(next)}
 draw();next();
 const mediaReady=()=>draw();video.addEventListener('loadeddata',mediaReady);video.addEventListener('emptied',mediaReady);
 const reset=()=>{frames=0;dimensionMismatch=false;ctx.setTransform(1,0,0,1,0,0);ctx.drawImage(poster,0,0,width,height);canvas.dataset.status='poster'};
 return {draw,reset,get state(){return {frames,dimensionMismatch,stopped}},destroy(){stopped=true;video.removeEventListener('loadeddata',mediaReady);video.removeEventListener('emptied',mediaReady);if(handle!==null){if(typeof video.cancelVideoFrameCallback==='function')video.cancelVideoFrameCallback(handle);else cancelAnimationFrame(handle)}reset();canvas.dataset.status='stopped'}};
}
