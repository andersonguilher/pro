const DB_NAME = "FiscalizaDB";
const STORE = "pendentes";

export function salvarOffline(tipo, dados) {
  const request = indexedDB.open(DB_NAME, 1);
  request.onupgradeneeded = (e) => {
    const db = e.target.result;
    if (!db.objectStoreNames.contains(STORE)) {
      db.createObjectStore(STORE, { autoIncrement: true });
    }
  };
  request.onsuccess = (e) => {
    const db = e.target.result;
    const tx = db.transaction(STORE, "readwrite");
    tx.objectStore(STORE).add({ tipo, dados });
    tx.oncomplete = () => {
      alert("💾 Salvo offline. Será sincronizado.");
    };
  };
}

export function sincronizar() {
  const request = indexedDB.open(DB_NAME, 1);
  request.onsuccess = (e) => {
    const db = e.target.result;
    const tx = db.transaction(STORE, "readwrite");
    const store = tx.objectStore(STORE);
    const tudo = store.getAll();

    tudo.onsuccess = async () => {
      for (const item of tudo.result) {
        let url = "";
        if (item.tipo === "1746") url = "../php/salvar_vistoria_1746.php";
        if (item.tipo === "local") url = "../php/salvar_vistoria_local.php";

        try {
          const formData = new FormData();
          for (const k in item.dados) {
            if (k === "fotos") {
              item.dados.fotos.forEach(f => formData.append("fotos[]", f));
            } else {
              formData.append(k, item.dados[k]);
            }
          }

          const res = await fetch(url, { method: "POST", body: formData });
          const json = await res.json();
          if (json.sucesso) store.delete(item.id);
        } catch (e) {
          console.warn("Falha ao sincronizar:", e);
        }
      }
    };
  };
}

// Tenta sincronizar sempre que online
window.addEventListener("online", sincronizar);
