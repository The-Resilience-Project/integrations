import { useState, useEffect, useRef } from "react";

function PicklistCombobox({ values, selected, onChange, loading, error }) {
  const [query, setQuery] = useState(selected || "");
  const [open, setOpen] = useState(false);
  const wrapperRef = useRef(null);

  // Sync external changes
  useEffect(() => {
    setQuery(selected || "");
  }, [selected]);

  // Close on outside click
  useEffect(() => {
    function handleClick(e) {
      if (wrapperRef.current && !wrapperRef.current.contains(e.target)) {
        setOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, []);

  const filtered = values.filter((v) =>
    v.toLowerCase().includes(query.toLowerCase())
  );

  function handleSelect(v) {
    onChange(v);
    setQuery(v);
    setOpen(false);
  }

  if (values.length === 0) {
    return (
      <>
        <input
          type="text"
          value={selected}
          onChange={(e) => onChange(e.target.value)}
          placeholder="e.g. NSWPDPN Delegate 2025"
          className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        {loading && (
          <p className="text-xs text-gray-400 mt-1">
            Loading picklist from vTiger...
          </p>
        )}
        {error && (
          <p className="text-xs text-amber-500 mt-1">
            {error} — enter the value manually
          </p>
        )}
      </>
    );
  }

  return (
    <div ref={wrapperRef} className="relative">
      <input
        type="text"
        value={query}
        onChange={(e) => {
          setQuery(e.target.value);
          onChange(e.target.value);
          setOpen(true);
        }}
        onFocus={() => setOpen(true)}
        placeholder="Type to search picklist..."
        className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      {open && filtered.length > 0 && (
        <ul className="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg">
          {filtered.map((v) => (
            <li
              key={v}
              onClick={() => handleSelect(v)}
              className={`px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 ${
                v === selected
                  ? "bg-blue-50 text-blue-700 font-medium"
                  : "text-gray-700"
              }`}
            >
              {v}
            </li>
          ))}
        </ul>
      )}
      {open && query && filtered.length === 0 && (
        <div className="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg px-3 py-2 text-sm text-gray-400">
          No matches
        </div>
      )}
      <p className="text-xs text-green-600 mt-1">
        {values.length} values from vTiger &quot;Forms Completed&quot; picklist
      </p>
    </div>
  );
}

export default function ImportConfig({ state, update, next, prev }) {
  const [picklistValues, setPicklistValues] = useState([]);
  const [picklistLoading, setPicklistLoading] = useState(false);
  const [picklistError, setPicklistError] = useState(null);

  const valid = state.sourceForm.trim().length > 0;

  useEffect(() => {
    fetchPicklist();
  }, []);

  async function fetchPicklist() {
    setPicklistLoading(true);
    setPicklistError(null);
    try {
      const res = await fetch(
        "/api/vtiger/picklist?module=Contacts&field=cf_contacts_formscompleted"
      );
      const data = await res.json();
      if (!res.ok) {
        setPicklistError(data.error || "Could not load picklist");
        return;
      }
      setPicklistValues(data.values || []);
    } catch {
      setPicklistError("Could not connect to vTiger API");
    } finally {
      setPicklistLoading(false);
    }
  }

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-medium text-gray-900 mb-4">
          Import Configuration
        </h2>

        {/* Service type */}
        <fieldset className="mb-6">
          <legend className="text-sm font-medium text-gray-700 mb-2">
            Service Type
          </legend>
          <div className="flex gap-4">
            {["School", "Workplace", "Early Years"].map((t) => (
              <label
                key={t}
                className={`flex items-center gap-2 px-4 py-2 rounded-md border cursor-pointer transition-colors ${
                  state.serviceType === t
                    ? "border-blue-500 bg-blue-50 text-blue-700"
                    : "border-gray-300 text-gray-600 hover:bg-gray-50"
                }`}
              >
                <input
                  type="radio"
                  name="serviceType"
                  value={t}
                  checked={state.serviceType === t}
                  onChange={() => update({ serviceType: t })}
                  className="sr-only"
                />
                {t}
              </label>
            ))}
          </div>
        </fieldset>

        {/* Source form */}
        <div className="mb-6">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Source Form Name
          </label>

          {/* Searchable picklist or plain text input */}
          <PicklistCombobox
            values={picklistValues}
            selected={state.sourceForm}
            onChange={(v) => update({ sourceForm: v })}
            loading={picklistLoading}
            error={picklistError}
          />

          <p className="text-xs text-gray-400 mt-1">
            Format: {"{Conference Name} {Conference Type} {Year}"}. Must match
            the vTiger picklist value exactly.
          </p>
        </div>

        {/* Endpoint type */}
        <fieldset>
          <legend className="text-sm font-medium text-gray-700 mb-2">
            API Endpoint
          </legend>
          <div className="flex gap-4">
            {["Prize Pack", "Enquiry"].map((ep) => (
              <label
                key={ep}
                className={`flex items-center gap-2 px-4 py-2 rounded-md border cursor-pointer transition-colors ${
                  state.endpointType === ep
                    ? "border-blue-500 bg-blue-50 text-blue-700"
                    : "border-gray-300 text-gray-600 hover:bg-gray-50"
                }`}
              >
                <input
                  type="radio"
                  name="endpointType"
                  value={ep}
                  checked={state.endpointType === ep}
                  onChange={() => update({ endpointType: ep })}
                  className="sr-only"
                />
                {ep}
              </label>
            ))}
          </div>
        </fieldset>

        {/* Enquiry warning */}
        {state.endpointType === "Enquiry" && (
          <div className="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-md">
            <p className="text-sm text-amber-800 font-medium">
              Important: Disable the workflow &quot;New enquiry - send email to
              enquirer&quot; in vTiger before proceeding.
            </p>
            <p className="text-xs text-amber-600 mt-1">
              Settings &rarr; Automation &rarr; Workflows
            </p>
          </div>
        )}
      </div>

      {/* Summary */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 className="text-md font-medium text-gray-900 mb-2">Summary</h3>
        <dl className="grid grid-cols-2 gap-2 text-sm">
          <dt className="text-gray-500">File</dt>
          <dd className="text-gray-900">{state.filename || "—"}</dd>
          <dt className="text-gray-500">Total rows</dt>
          <dd className="text-gray-900">{state.totalRows}</dd>
          <dt className="text-gray-500">Service type</dt>
          <dd className="text-gray-900">{state.serviceType}</dd>
          <dt className="text-gray-500">Source form</dt>
          <dd className="text-gray-900">{state.sourceForm || "—"}</dd>
          <dt className="text-gray-500">Endpoint</dt>
          <dd className="text-gray-900">{state.endpointType}</dd>
        </dl>
      </div>

      {/* Navigation */}
      <div className="flex justify-between">
        <button
          onClick={prev}
          className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900"
        >
          &larr; Back
        </button>
        <button
          onClick={next}
          disabled={!valid}
          className={`px-6 py-2 rounded-md text-sm font-medium text-white transition-colors ${
            !valid
              ? "bg-gray-300 cursor-not-allowed"
              : "bg-blue-600 hover:bg-blue-700"
          }`}
        >
          Next: Proof &rarr;
        </button>
      </div>
    </div>
  );
}
