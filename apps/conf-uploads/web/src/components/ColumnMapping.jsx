import { useState, useEffect } from "react";

const FIELDS = [
  { key: "first_name", label: "First Name", required: true },
  { key: "last_name", label: "Last Name", required: true },
  { key: "email", label: "Email", required: true },
  { key: "org", label: "Organisation", required: true },
  { key: "num_of_students", label: "Number of Students" },
  { key: "job_title", label: "Job Title" },
  { key: "phone", label: "Phone" },
  { key: "state", label: "State" },
  { key: "enquiry", label: "Enquiry" },
];

export default function ColumnMapping({ state, update, next, prev }) {
  const [mapping, setMapping] = useState(state.columnMapping || {});
  const [previewRows, setPreviewRows] = useState([]);
  const [loading, setLoading] = useState(false);

  // Fetch headers + mapping on mount if not already present
  useEffect(() => {
    if (state.headers && state.headers.length > 0) {
      setMapping(state.columnMapping || {});
      fetchPreview(state.columnMapping || {});
    } else if (state.filePath) {
      // Need to detect mapping for existing file selection
      detectMapping();
    }
  }, []);

  async function detectMapping() {
    try {
      const res = await fetch("/api/upload", {
        method: "POST",
        // For existing files, we need a different approach
        // Let's use a workaround — read with empty mapping first
      });
    } catch {
      // Silently handle — mapping will need manual setup
    }
  }

  async function fetchPreview(currentMapping) {
    if (!state.filePath) return;
    setLoading(true);
    try {
      const res = await fetch("/api/preview", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          file_path: state.filePath,
          column_mapping: currentMapping,
        }),
      });
      const data = await res.json();
      if (res.ok) {
        setPreviewRows(data.mapped_rows || []);
      }
    } catch {
      // Silently handle preview errors
    } finally {
      setLoading(false);
    }
  }

  function handleChange(fieldKey, colIndex) {
    const newMapping = { ...mapping };
    if (colIndex === "") {
      delete newMapping[fieldKey];
    } else {
      newMapping[fieldKey] = parseInt(colIndex, 10);
    }
    setMapping(newMapping);
    update({ columnMapping: newMapping });
    fetchPreview(newMapping);
  }

  const requiredMissing = FIELDS.filter(
    (f) => f.required && (mapping[f.key] === undefined || mapping[f.key] === null)
  );

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-medium text-gray-900 mb-1">
          Column Mapping
        </h2>
        <p className="text-sm text-gray-500 mb-4">
          Map each field to a column from your file. Required fields are marked
          with *.
        </p>

        {state.headers && state.headers.length > 0 ? (
          <div className="grid grid-cols-2 gap-4">
            {FIELDS.map((field) => (
              <div key={field.key}>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {field.label}
                  {field.required && (
                    <span className="text-red-500 ml-1">*</span>
                  )}
                </label>
                <select
                  value={mapping[field.key] ?? ""}
                  onChange={(e) => handleChange(field.key, e.target.value)}
                  className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">— Not mapped —</option>
                  {state.headers.map((h, i) => (
                    <option key={i} value={i}>
                      {h} (col {i})
                    </option>
                  ))}
                </select>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-gray-500">
            No headers detected. Please go back and upload a file.
          </p>
        )}
      </div>

      {/* Preview table */}
      {previewRows.length > 0 && (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-md font-medium text-gray-900 mb-3">
            Preview (first {previewRows.length} rows)
          </h3>
          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b border-gray-200">
                  {FIELDS.filter((f) => mapping[f.key] !== undefined).map(
                    (f) => (
                      <th
                        key={f.key}
                        className="text-left py-2 px-3 font-medium text-gray-700"
                      >
                        {f.label}
                      </th>
                    )
                  )}
                </tr>
              </thead>
              <tbody>
                {previewRows.map((row, i) => (
                  <tr key={i} className="border-b border-gray-100">
                    {FIELDS.filter((f) => mapping[f.key] !== undefined).map(
                      (f) => (
                        <td key={f.key} className="py-2 px-3 text-gray-600">
                          {row[f.key] || "—"}
                        </td>
                      )
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {state.totalRows > 20 && (
            <p className="text-xs text-gray-400 mt-2">
              Showing 20 of {state.totalRows} rows
            </p>
          )}
        </div>
      )}

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
          disabled={requiredMissing.length > 0}
          className={`px-6 py-2 rounded-md text-sm font-medium text-white transition-colors ${
            requiredMissing.length > 0
              ? "bg-gray-300 cursor-not-allowed"
              : "bg-blue-600 hover:bg-blue-700"
          }`}
        >
          Next &rarr;
        </button>
      </div>

      {requiredMissing.length > 0 && (
        <p className="text-sm text-amber-600">
          Missing required mappings:{" "}
          {requiredMissing.map((f) => f.label).join(", ")}
        </p>
      )}
    </div>
  );
}
