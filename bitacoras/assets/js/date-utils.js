// date-utils.js
// Utilidades de fecha (global)
// Convierte fechas tipo MySQL "YYYY-MM-DD HH:MM:SS" (sin TZ) a hora local del navegador.

(function (w) {
  if (!w) return;

  function isIsoWithTz(value) {
    return typeof value === 'string' && /T.*(Z|[+-]\d{2}:?\d{2})$/.test(value);
  }

  /**
   * Convierte una fecha tipo MySQL "YYYY-MM-DD HH:MM:SS" a ISO.
   * Por defecto asume que viene en UTC (sin zona horaria) y le agrega 'Z'.
   */
  function mysqlToIso(value, assumeUtc) {
    if (value == null) return '';
    if (value instanceof Date) return value.toISOString();

    const str = String(value).trim();
    if (!str) return '';
    if (isIsoWithTz(str)) return str;

    // Fecha sin hora: YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
      const baseDateOnly = str + 'T00:00:00';
      return assumeUtc ? (baseDateOnly + 'Z') : baseDateOnly;
    }

    const base = str.includes('T') ? str : str.replace(' ', 'T');
    return assumeUtc ? (base.endsWith('Z') ? base : base + 'Z') : base;
  }

  /**
   * Formatea una fecha a hora local del navegador.
   * - Si recibe MySQL DATETIME sin zona, asume UTC por defecto.
   */
  w.bcFormatFechaLocal = function (fecha, opts) {
    const assumeUtc = !opts || opts.assumeUtc !== false;
    const iso = mysqlToIso(fecha, assumeUtc);
    if (!iso) return '-';

    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return String(fecha);
    return d.toLocaleString();
  };

  /**
   * Igual que bcFormatFechaLocal, pero solo devuelve la fecha (sin hora).
   */
  w.bcFormatFechaLocalDate = function (fecha, opts) {
    const assumeUtc = !opts || opts.assumeUtc !== false;
    const iso = mysqlToIso(fecha, assumeUtc);
    if (!iso) return '---';

    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return String(fecha);
    return d.toLocaleDateString();
  };

  /**
   * Devuelve la fecha local en formato YYYY-MM-DD (para inputs type=date).
   */
  w.bcLocalISODate = function (fecha, opts) {
    const assumeUtc = !opts || opts.assumeUtc !== false;
    const iso = mysqlToIso(fecha, assumeUtc);
    if (!iso) return '';

    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';

    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  };
})(window);
