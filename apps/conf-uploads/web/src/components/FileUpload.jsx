import { useState, useRef, useEffect } from "react";

export default function FileUpload({ state, update, next }) {
  const [dragging, setDragging] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState(null);
  const [existingFiles, setExistingFiles] = useState([]);
  const fileInput = useRef(null);

  useEffect(() => {
    fetch("/api/files")
      .then((r) => r.json())
      .then((data) => setExistingFiles(data.files || []))
      .catch(() => {});
  }, []);

  async function uploadFile(file) {
    setUploading(true);
    setError(null);
    const form = new FormData();
    form.append("file", file);

    try {
      const res = await fetch("/api/upload", { method: "POST", body: form });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Upload failed");

      update({
        filePath: data.file_path,
        filename: data.filename,
        headers: data.headers,
        columnMapping: data.column_mapping,
        previewRows: data.preview_rows,
        totalRows: data.total_rows,
      });
      next();
    } catch (e) {
      setError(e.message);
    } finally {
      setUploading(false);
    }
  }

  async function selectExisting(filePath, filename) {
    setUploading(true);
    setError(null);

    try {
      const res = await fetch("/api/detect", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ file_path: filePath }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Detection failed");

      update({
        filePath: data.file_path,
        filename: data.filename,
        headers: data.headers,
        columnMapping: data.column_mapping,
        previewRows: data.preview_rows,
        totalRows: data.total_rows,
      });
      next();
    } catch (e) {
      setError(e.message);
    } finally {
      setUploading(false);
    }
  }

  function handleDrop(e) {
    e.preventDefault();
    setDragging(false);
    const file = e.dataTransfer.files[0];
    if (file) uploadFile(file);
  }

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-medium text-gray-900 mb-4">
          Upload TSV File
        </h2>

        {/* Drop zone */}
        <div
          onDragOver={(e) => {
            e.preventDefault();
            setDragging(true);
          }}
          onDragLeave={() => setDragging(false)}
          onDrop={handleDrop}
          onClick={() => fileInput.current?.click()}
          className={`border-2 border-dashed rounded-lg p-12 text-center cursor-pointer transition-colors ${
            dragging
              ? "border-blue-400 bg-blue-50"
              : "border-gray-300 hover:border-gray-400"
          }`}
        >
          <input
            ref={fileInput}
            type="file"
            accept=".tsv,.txt"
            className="hidden"
            onChange={(e) => e.target.files[0] && uploadFile(e.target.files[0])}
          />
          {uploading ? (
            <p className="text-gray-500">Uploading...</p>
          ) : (
            <>
              <p className="text-gray-600 font-medium">
                Drop a TSV file here or click to browse
              </p>
              <p className="text-gray-400 text-sm mt-1">
                Tab-separated values file from Google Sheets
              </p>
            </>
          )}
        </div>

        {error && (
          <p className="mt-3 text-red-600 text-sm">{error}</p>
        )}
      </div>

      {/* Existing files */}
      {existingFiles.length > 0 && (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-md font-medium text-gray-900 mb-3">
            Or select an existing file
          </h3>
          <ul className="space-y-2">
            {existingFiles.map((f) => (
              <li key={f.path}>
                <button
                  onClick={() => selectExisting(f.path, f.name)}
                  className="text-blue-600 hover:text-blue-800 hover:underline text-sm"
                >
                  {f.name}
                </button>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
