/* global pdfjsLib, PDFLib */
(function(){
  const canvas = document.getElementById("pdfCanvas");
  const ctx = canvas.getContext("2d");
  const viewer = document.getElementById("viewer");
  const msg = document.getElementById("msg");

  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");
  const saveBtn = document.getElementById("saveBtn");
  const sigScale = document.getElementById("sigScale");
  const addSigBtn = document.getElementById("addSigBtn");
  const dupSigBtn = document.getElementById("dupSigBtn");
  const delSigBtn = document.getElementById("delSigBtn");

  const pageNumEl = document.getElementById("pageNum");
  const pageCountEl = document.getElementById("pageCount");

  function setMsg(t){ msg.textContent = t || ""; }

  // show JS errors in UI (helps debugging)
  window.addEventListener("error", (e) => {
    const m = (e && e.message) ? e.message : "Unknown JS error";
    setMsg("JS Error: " + m);
  });
  window.addEventListener("unhandledrejection", (e) => {
    const m = (e && e.reason && e.reason.message) ? e.reason.message : String(e.reason || "Promise error");
    setMsg("Promise Error: " + m);
  });

  if (!window.pdfjsLib) {
    setMsg("PDF engine belum termuat (pdfjsLib). Pastikan internet tersedia / CDN tidak diblokir.");
    return;
  }
  // worker for pdf.js (CDNJS stable UMD)
  pdfjsLib.GlobalWorkerOptions.workerSrc =
    "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";

  let pdfDoc = null;
  let pageNum = 1;
  let viewport = null;
  let pdfBytes = null;

  // placements store ratios
  let placements = [];
  let activeId = null;

  function uid(){ return "s" + Math.random().toString(16).slice(2) + Date.now().toString(16); }

  function setActive(id){
    activeId = id;
    placements.forEach(p => {
      if (!p.el) return;
      p.el.classList.toggle("active", p.id === id);
    });
  }
  function getActive(){ return placements.find(p => p.id === activeId) || null; }

  function clampPx(x, y, w, h, vw, vh){
    return {
      x: Math.max(0, Math.min(x, vw - w)),
      y: Math.max(0, Math.min(y, vh - h))
    };
  }

  function applyTransform(p){
    const vw = canvas.width, vh = canvas.height;
    const w = p.el.offsetWidth;
    const h = p.el.offsetHeight;

    const clamped = clampPx(p.pendingX, p.pendingY, w, h, vw, vh);
    p.pendingX = clamped.x;
    p.pendingY = clamped.y;

    p.el.style.transform = `translate3d(${p.pendingX}px, ${p.pendingY}px, 0)`;

    // update ratios
    p.xR = (p.pendingX / vw);
    p.yR = (p.pendingY / vh);
    p.wR = (w / vw);
    p.hR = (h / vh);

    p.rafId = null;
  }
  function scheduleApply(p){
    if (p.rafId) return;
    p.rafId = requestAnimationFrame(() => applyTransform(p));
  }

  function attachDragHandlers(p){
    p.el.addEventListener("pointerdown", (e) => {
      setActive(p.id);
      p.dragging = true;
      p.el.setPointerCapture(e.pointerId);

      p.startX = e.clientX;
      p.startY = e.clientY;

      const vw = canvas.width, vh = canvas.height;
      p.startSigX = p.xR * vw;
      p.startSigY = p.yR * vh;
      p.pendingX = p.startSigX;
      p.pendingY = p.startSigY;
    });

    p.el.addEventListener("pointermove", (e) => {
      if (!p.dragging) return;
      const dx = e.clientX - p.startX;
      const dy = e.clientY - p.startY;
      p.pendingX = p.startSigX + dx;
      p.pendingY = p.startSigY + dy;
      scheduleApply(p);
    });

    const up = (e) => {
      p.dragging = false;
      try { p.el.releasePointerCapture(e.pointerId); } catch {}
    };
    p.el.addEventListener("pointerup", up);
    p.el.addEventListener("pointercancel", up);
    p.el.addEventListener("click", () => setActive(p.id));
  }

  function syncOverlaysForPage(){
    const vw = canvas.width, vh = canvas.height;

    placements.forEach(p => {
      const shouldShow = (p.page === pageNum);

      if (shouldShow) {
        if (!p.el.parentNode) viewer.appendChild(p.el);

        // keep size
        const wPx = Math.max(30, Math.round(p.wR * vw));
        p.el.style.width = wPx + "px";

        p.pendingX = p.xR * vw;
        p.pendingY = p.yR * vh;
        scheduleApply(p);
      } else {
        if (p.el.parentNode) p.el.parentNode.removeChild(p.el);
      }
    });

    setActive(activeId);
  }

  function createPlacement(fromPlacement){
    const id = uid();
    const img = document.createElement("img");
    img.src = "signature_image.php";
    img.className = "sig";
    img.alt = "signature";
    img.draggable = false;

    const p = {
      id,
      page: fromPlacement ? fromPlacement.page : pageNum,
      xR: fromPlacement ? fromPlacement.xR : 0.12,
      yR: fromPlacement ? fromPlacement.yR : 0.18,
      wR: fromPlacement ? fromPlacement.wR : 0.22,
      hR: fromPlacement ? fromPlacement.hR : 0.10,
      el: img,
      dragging: false,
      rafId: null,
      pendingX: 0,
      pendingY: 0,
      startX: 0,
      startY: 0,
      startSigX: 0,
      startSigY: 0
    };

    attachDragHandlers(p);
    placements.push(p);

    img.onload = () => {
      // match scale slider for new signature
      const baseW = 240 * (Number(sigScale.value)/100);
      if (!fromPlacement) img.style.width = Math.round(baseW) + "px";
      if (fromPlacement && fromPlacement.el) img.style.width = fromPlacement.el.style.width || (fromPlacement.el.offsetWidth + "px");

      // initial position (offset if cloned)
      const vw = canvas.width || 800;
      const vh = canvas.height || 600;
      let xPx = p.xR * vw;
      let yPx = p.yR * vh;
      if (fromPlacement) { xPx += 18; yPx += 18; }

      p.pendingX = xPx;
      p.pendingY = yPx;

      // attach & compute accurate ratios
      if (!img.parentNode && p.page === pageNum) viewer.appendChild(img);
      applyTransform(p);
      setActive(p.id);
      syncOverlaysForPage();
    };

    syncOverlaysForPage();
  }

  function removeActive(){
    const a = getActive();
    if (!a) return;
    if (a.el && a.el.parentNode) a.el.parentNode.removeChild(a.el);
    placements = placements.filter(p => p.id !== a.id);
    activeId = placements.length ? placements[placements.length-1].id : null;
    setActive(activeId);
  }

  addSigBtn.addEventListener("click", () => createPlacement(null));
  dupSigBtn.addEventListener("click", () => {
    const a = getActive();
    if (!a) return;
    createPlacement(a);
  });
  delSigBtn.addEventListener("click", () => removeActive());

  sigScale.addEventListener("input", () => {
    const a = getActive();
    if (!a || !a.el) return;
    const v = Number(sigScale.value);
    const baseW = 240 * (v/100);
    a.el.style.width = Math.round(baseW) + "px";
    scheduleApply(a);
  });

  async function loadPdf(){
    setMsg("Memuat PDF...");
    const res = await fetch(window.__PDF_URL__, { cache: "no-store" });
    if (!res.ok) throw new Error("Gagal ambil PDF (" + res.status + ")");
    pdfBytes = await res.arrayBuffer();
    pdfDoc = await pdfjsLib.getDocument({ data: pdfBytes }).promise;
    pageCountEl.textContent = String(pdfDoc.numPages);
    await renderPage(1);
    setMsg("");
  }

  async function renderPage(num){
    pageNum = num;
    pageNumEl.textContent = String(pageNum);

    const page = await pdfDoc.getPage(pageNum);

    const desiredWidth = Math.min(900, viewer.clientWidth - 24);
    const unscaled = page.getViewport({ scale: 1 });
    const scale = desiredWidth / unscaled.width;

    viewport = page.getViewport({ scale });
    canvas.width = Math.floor(viewport.width);
    canvas.height = Math.floor(viewport.height);

    await page.render({ canvasContext: ctx, viewport }).promise;
    syncOverlaysForPage();
  }

  prevBtn.addEventListener("click", async () => {
    if (!pdfDoc || pageNum <= 1) return;
    await renderPage(pageNum - 1);
  });
  nextBtn.addEventListener("click", async () => {
    if (!pdfDoc || pageNum >= pdfDoc.numPages) return;
    await renderPage(pageNum + 1);
  });

  function placementToPdf(p, pdfPageWidth, pdfPageHeight){
    const x = p.xR * pdfPageWidth;
    const y = (1 - p.yR - p.hR) * pdfPageHeight;
    const w = p.wR * pdfPageWidth;
    const h = p.hR * pdfPageHeight;
    return { x, y, w, h };
  }

  async function makeSignedPdf(){
    setMsg("Menyusun PDF bertanda tangan...");
    if (!window.PDFLib) throw new Error("PDF-lib belum termuat (PDFLib).");

    const { PDFDocument } = PDFLib;
    const pdfLibDoc = await PDFDocument.load(pdfBytes);

    // signature png (admin-only endpoint)
    const sigRes = await fetch("signature_image.php", { cache: "no-store" });
    if (!sigRes.ok) throw new Error("Tanda tangan belum diupload / tidak dapat diakses.");
    const sigBytes = await sigRes.arrayBuffer();
    const sigImg = await pdfLibDoc.embedPng(sigBytes);

    const pages = pdfLibDoc.getPages();
    placements.forEach(p => {
      const page = pages[p.page - 1];
      if (!page) return;
      const pw = page.getWidth();
      const ph = page.getHeight();
      const pos = placementToPdf(p, pw, ph);
      page.drawImage(sigImg, { x: pos.x, y: pos.y, width: pos.w, height: pos.h });
    });

    const outBytes = await pdfLibDoc.save();
    return outBytes;
  }

  function arrayBufferToBase64(buffer) {
    let binary = "";
    const bytes = new Uint8Array(buffer);
    const chunk = 0x8000;
    for (let i = 0; i < bytes.length; i += chunk) {
      binary += String.fromCharCode.apply(null, bytes.subarray(i, i + chunk));
    }
    return btoa(binary);
  }

  saveBtn.addEventListener("click", async () => {
    try{
      saveBtn.disabled = true;
      const out = await makeSignedPdf();
      const b64 = arrayBufferToBase64(out);

      const fd = new FormData();
      fd.append("f", window.__FILE_KEY__);
      fd.append("signed_pdf_b64", b64);

      setMsg("Menyimpan ke OneDrive...");
      const resp = await fetch("save.php", { method: "POST", body: fd });
      const j = await resp.json().catch(()=>null);
      if (!resp.ok || !j || !j.ok) {
        throw new Error((j && j.error) ? j.error : ("Gagal menyimpan (" + resp.status + ")"));
      }
      window.location.href = j.status_url;
    }catch(e){
      console.error(e);
      setMsg("Error: " + (e.message || e));
      saveBtn.disabled = false;
    }
  });

  // auto-add one signature as default (nice UX)
  // but only after first render (so canvas size known)
  (async () => {
    try{
      await loadPdf();
      createPlacement(null);
    }catch(e){
      console.error(e);
      setMsg("Error: " + (e.message || e));
    }
  })();
})();
