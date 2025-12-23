async function copyText(t){
  try{
    await navigator.clipboard.writeText(t);
    alert("Link disalin ✅");
  }catch(e){
    prompt("Copy manual:", t);
  }
}

async function genToken(docId){
  const fd = new FormData();
  fd.append("doc_id", docId);

  const resp = await fetch("admin_generate_token.php", { method:"POST", body: fd });
  const j = await resp.json();
  if(!resp.ok || !j.ok){
    alert("Gagal generate token: " + (j.error || resp.status));
    return;
  }
  const link = j.sign_url;
  // show prompt & copy
  await copyText(link);
}
