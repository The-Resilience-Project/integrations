import { useState, useEffect, useRef } from "react";

export default function ImportResults({ state, update, goTo }) {
  const [rows, setRows] = useState([]);
  const [total, setTotal] = useState(0);
  const [current, setCurrent] = useState(0);
  const [successCount, setSuccessCount] = useState(0);
  const [failCount, setFailCount] = useState(0);
  const [done, setDone] = useState(false);
  const [error, setError] = useState(null);
  const [expandedRow, setExpandedRow] = useState(null);
  const [vtLookup, setVtLookup] = useState(null);
  const [vtLoading, setVtLoading] = useState(false);
  const [vtCurrent, setVtCurrent] = useState(0);
  const hasRun = useRef(false);

  useEffect(() => {
    if (hasRun.current) return;
    hasRun.current = true;
    runImport();
  }, []);

  async function runImport(mode, testLimit) {
    const importMode = mode || state.importMode || "full";
    const limit = testLimit || state.importTestLimit || 1;

    // Reset state
    setRows([]);
    setCurrent(0);
    setSuccessCount(0);
    setFailCount(0);
    setDone(false);
    setError(null);

    // Build list of row indices to import
    const skipRows = new Set(state.skipRows || []);
    let rowIndices = [];
    for (let i = 0; i < state.totalRows; i++) {
      if (!skipRows.has(i)) rowIndices.push(i);
    }
    if (importMode === "test") {
      rowIndices = rowIndices.slice(0, limit);
    }

    setTotal(rowIndices.length);

    // Send one row at a time with a 1.5s gap to avoid rate limiting
    const DELAY_MS = 1500;
    let successes = 0;
    let failures = 0;

    for (let idx = 0; idx < rowIndices.length; idx++) {
      // Delay between requests (not before the first one)
      if (idx > 0) {
        await new Promise((r) => setTimeout(r, DELAY_MS));
      }
      const rowNum = rowIndices[idx];
      try {
        const res = await fetch("/api/import-one", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            file_path: state.filePath,
            column_mapping: state.columnMapping,
            service_type: state.serviceType,
            source_form: state.sourceForm,
            endpoint_type: state.endpointType,
            row: rowNum,
          }),
        });
        const result = await res.json();

        if (!res.ok) {
          result.success = false;
          result.row = rowNum;
          result.error = result.error || `HTTP ${res.status}`;
        }

        if (result.success) {
          successes++;
        } else {
          failures++;
        }

        setRows((prev) => [...prev, result]);
        setCurrent(idx + 1);
        setSuccessCount(successes);
        setFailCount(failures);
      } catch (e) {
        failures++;
        setRows((prev) => [
          ...prev,
          { row: rowNum, success: false, error: e.message, status_code: 0 },
        ]);
        setCurrent(idx + 1);
        setFailCount(failures);
      }
    }

    setDone(true);
  }

  async function checkVtiger() {
    const emails = rows.map((r) => r.email).filter(Boolean);
    if (emails.length === 0) return;

    setVtLoading(true);
    setVtCurrent(0);

    try {
      const res = await fetch("/api/vtiger/lookup", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          emails: emails,
          source_form: state.sourceForm,
        }),
      });
      const data = await res.json();
      if (data.results) {
        setVtLookup(data.results);
      }
      setVtCurrent(emails.length);
    } catch {
      const results = {};
      for (const email of emails) {
        results[email] = { found: false, has_tag: false, error: "Request failed" };
      }
      setVtLookup(results);
    }

    setVtLoading(false);
  }

  const isTestMode = (state.importMode || "full") === "test";
  const pct = total > 0 ? Math.round((current / total) * 100) : 0;

  return (
    <div className="space-y-6">
      {/* Progress bar + summary */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-lg font-medium text-gray-900 mb-4">
          Import {done ? "Results" : "Progress"}
          {isTestMode && (
            <span className="ml-2 text-sm font-normal text-amber-600">
              (Test Mode)
            </span>
          )}
        </h2>

        {/* Progress bar */}
        <div className="mb-4">
          <div className="flex items-center justify-between text-sm mb-1">
            <span className="text-gray-600">
              {done ? "Complete" : `Processing row ${current} of ${total}...`}
            </span>
            <span className="font-medium text-gray-900">{pct}%</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div
              className={`h-3 rounded-full transition-all duration-300 ${
                done && failCount === 0
                  ? "bg-green-500"
                  : failCount > 0
                    ? "bg-amber-500"
                    : "bg-blue-600"
              }`}
              style={{ width: `${pct}%` }}
            />
          </div>
        </div>

        {/* Counts */}
        <div className="grid grid-cols-3 gap-4 mb-4">
          <div className="text-center p-3 bg-gray-50 rounded-lg">
            <p className="text-xl font-bold text-gray-900">
              {current} / {total}
            </p>
            <p className="text-xs text-gray-500">Processed</p>
          </div>
          <div className="text-center p-3 bg-green-50 rounded-lg">
            <p className="text-xl font-bold text-green-700">{successCount}</p>
            <p className="text-xs text-green-600">Succeeded</p>
          </div>
          <div className="text-center p-3 bg-red-50 rounded-lg">
            <p className="text-xl font-bold text-red-700">{failCount}</p>
            <p className="text-xs text-red-600">Failed</p>
          </div>
        </div>

        {error && <p className="text-red-600 text-sm mb-4">Error: {error}</p>}

        {/* Results list — rows appear one by one */}
        {rows.length > 0 && (
          <div className="space-y-3">
            {rows.map((r) => (
              <div
                key={r.row}
                className={`border rounded-lg overflow-hidden ${
                  r.success ? "border-green-200" : "border-red-200"
                }`}
              >
                <button
                  onClick={() =>
                    setExpandedRow(expandedRow === r.row ? null : r.row)
                  }
                  className={`w-full flex items-center justify-between px-4 py-3 text-sm text-left hover:bg-gray-50 transition-colors ${
                    r.success ? "bg-green-50/50" : "bg-red-50/50"
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <span
                      className={`w-2.5 h-2.5 rounded-full flex-shrink-0 ${
                        r.success ? "bg-green-500" : "bg-red-500"
                      }`}
                    />
                    <span className="font-medium text-gray-900">
                      Row {r.row}
                    </span>
                    <span className="text-gray-600">{r.email}</span>
                    <span
                      className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                        r.success
                          ? "bg-green-100 text-green-800"
                          : "bg-red-100 text-red-800"
                      }`}
                    >
                      {r.status_code} {r.success ? "OK" : "FAIL"}
                      {r.attempts > 1 ? ` (${r.attempts} attempts)` : ""}
                    </span>
                    {r.error && (
                      <span className="text-xs text-red-600">{r.error}</span>
                    )}
                    {vtLookup && r.email && vtLookup[r.email] && (() => {
                      const vt = vtLookup[r.email];
                      if (vt.has_tag) {
                        return (
                          <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                            Already tagged
                          </span>
                        );
                      } else if (vt.found) {
                        return (
                          <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            Exists, no tag
                          </span>
                        );
                      } else {
                        return (
                          <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                            New
                          </span>
                        );
                      }
                    })()}
                  </div>
                  <span className="text-gray-400 text-xs">
                    {expandedRow === r.row ? "Hide" : "Show"} details
                  </span>
                </button>

                {expandedRow === r.row && (
                  <div className="border-t border-gray-200 bg-gray-50 px-4 py-4 space-y-4">
                    <div>
                      <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                        Endpoint
                      </h4>
                      <p className="text-sm font-mono text-gray-700">
                        POST {r.endpoint}
                      </p>
                    </div>
                    <div>
                      <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                        Request Body
                      </h4>
                      <pre className="text-xs bg-gray-900 text-gray-100 rounded-md p-3 overflow-x-auto whitespace-pre-wrap">
                        {JSON.stringify(r.request_body, null, 2)}
                      </pre>
                    </div>
                    <div>
                      <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                        Response ({r.status_code})
                      </h4>
                      <pre className="text-xs bg-gray-900 text-gray-100 rounded-md p-3 overflow-x-auto whitespace-pre-wrap">
                        {typeof r.response_body === "object"
                          ? JSON.stringify(r.response_body, null, 2)
                          : r.response_body || "(empty)"}
                      </pre>
                    </div>
                    {vtLookup && r.email && vtLookup[r.email]?.found && (
                      <div>
                        <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                          vTiger Contact Record
                        </h4>
                        <pre className="text-xs bg-blue-950 text-blue-100 rounded-md p-3 overflow-x-auto whitespace-pre-wrap">
                          {JSON.stringify(vtLookup[r.email].contacts, null, 2)}
                        </pre>
                      </div>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Enquiry reminder */}
      {done && state.endpointType === "Enquiry" && (
        <div className="p-4 bg-amber-50 border border-amber-200 rounded-md">
          <p className="text-sm text-amber-800 font-medium">
            Reminder: Re-enable workflow &quot;New enquiry - send email to
            enquirer&quot; in vTiger.
          </p>
          <p className="text-xs text-amber-600 mt-1">
            Settings &rarr; Automation &rarr; Workflows
          </p>
        </div>
      )}

      {/* vTiger verification */}
      {done && (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-md font-medium text-gray-900">
                Verify in vTiger
              </h3>
              <p className="text-sm text-gray-500">
                Check which contacts actually exist in vTiger by email
              </p>
            </div>
            <button
              onClick={checkVtiger}
              disabled={vtLoading}
              className={`px-4 py-2 rounded-md text-sm font-medium text-white transition-colors ${
                vtLoading
                  ? "bg-gray-400 cursor-not-allowed"
                  : "bg-indigo-600 hover:bg-indigo-700"
              }`}
            >
              {vtLoading
                ? `Checking ${vtCurrent} of ${rows.length}...`
                : vtLookup
                  ? "Re-check vTiger"
                  : "Check vTiger"}
            </button>
          </div>
          {vtLookup && (
            <div className="mt-4 grid grid-cols-2 gap-4">
              <div className="text-center p-3 bg-blue-50 rounded-lg">
                <p className="text-xl font-bold text-blue-700">
                  {Object.values(vtLookup).filter((v) => v.found).length}
                </p>
                <p className="text-xs text-blue-600">Found in vTiger</p>
              </div>
              <div className="text-center p-3 bg-gray-50 rounded-lg">
                <p className="text-xl font-bold text-gray-700">
                  {Object.values(vtLookup).filter((v) => !v.found).length}
                </p>
                <p className="text-xs text-gray-500">Not found</p>
              </div>
            </div>
          )}
        </div>
      )}

      {/* Actions — only show when done */}
      {done && (
        <div className="flex justify-between items-center">
          <button
            onClick={() => goTo(3)}
            className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900"
          >
            &larr; Back to Proof
          </button>
          <div className="flex gap-3">
            {isTestMode && (
              <button
                onClick={() => runImport("full", 0)}
                className="px-6 py-2 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors"
              >
                Run All Remaining
              </button>
            )}
            <button
              onClick={() => goTo(0)}
              className="px-4 py-2 rounded-md text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Start Over
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
