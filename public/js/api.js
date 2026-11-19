/**
 * api.js — Merkezi fetch yardımcısı
 * Tüm API çağrıları buradan geçer; hata yönetimi merkezi.
 */
const API_BASE = '/api';

const Api = {
  async request(method, path, body = null) {
    const opts = {
      method,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(API_BASE + path, opts);
    const json = await res.json().catch(() => ({ success: false, message: 'Yanıt parse edilemedi.' }));
    if (!res.ok && !json.success) throw { status: res.status, ...json };
    return json;
  },
  get:    (path)        => Api.request('GET',    path),
  post:   (path, body)  => Api.request('POST',   path, body),
  put:    (path, body)  => Api.request('PUT',    path, body),
  delete: (path)        => Api.request('DELETE', path),
};
