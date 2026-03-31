import { useState, useEffect } from "react";

export default function ProofReview({ state, update, next, prev, goTo }) {
  const [bodies, setBodies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [expandedRow, setExpandedRow] = useState(null);
  const [vtLookup, setVtLookup] = useState(null);
  const [vtLoading, setVtLoading] = useState(false);
  const [vtCurrent, setVtCurrent] = useState(0);

  useEffect(() => {
    fetchProof();
  }, []);

  async function fetchProof() {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch("/api/proof", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          file_path: state.filePath,
          column_mapping: state.columnMapping,
          service_type: state.serviceType,
          source_form: state.sourceForm,
          endpoint_type: state.endpointType,
        }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Proof failed");
      setBodies(data.bodies || []);
      update({ proofBodies: data.bodies || [] });
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  async function checkVtiger() {
    const emails = bodies
      .map(({ body }) => body.contact_email)
      .filter(Boolean);
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
        update({ vtLookup: data.results });
      }
      setVtCurrent(emails.length);
    } catch {
      // Fallback: mark all as unknown
      const results = {};
      for (const email of emails) {
        results[email] = { found: false, has_tag: false, error: "Request failed" };
      }
      setVtLookup(results);
    }

    setVtLoading(false);
  }

  function startImport(mode, testLimit, skipTagged) {
    // Build the list of row indices to import
    let skipRows = [];
    if (skipTagged && vtLookup) {
      skipRows = bodies
        .filter(({ body }) => {
          const vt = vtLookup[body.contact_email];
          return vt && vt.has_tag;
        })
        .map(({ row }) => row);
    }

    update({
      importMode: mode,
      importTestLimit: testLimit,
      skipRows: skipRows,
    });
    next();
  }

  if (loading) {
    return (
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <p className="text-gray-500">Building request bodies (dry run)...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <p className="text-red-600">Error: {error}</p>
        <button
          onClick={prev}
          className="mt-4 px-4 py-2 text-sm text-gray-600 hover:text-gray-900"
        >
          &larr; Back
        </button>
      </div>
    );
  }

  // Compute vTiger stats
  const vtValues = vtLookup ? Object.values(vtLookup) : [];
  const taggedCount = vtValues.filter((v) => v.has_tag).length;
  const existsNoTagCount = vtValues.filter(
    (v) => v.found && !v.has_tag
  ).length;
  const newCount = vtValues.filter((v) => !v.found).length;
  const importableCount = bodies.length - taggedCount;

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h2 className="text-lg font-medium text-gray-900">Proof Review</h2>
            <p className="text-sm text-gray-500">
              {bodies.length} request bodies built. Review before importing.
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
              ? `Checking ${vtCurrent} of ${bodies.length}...`
              : vtLookup
                ? "Re-check vTiger"
                : "Check vTiger"}
          </button>
        </div>

        {/* vTiger summary */}
        {vtLookup && (
          <div className="grid grid-cols-3 gap-4 mb-4">
            <div className="text-center p-3 bg-amber-50 rounded-lg">
              <p className="text-xl font-bold text-amber-700">{taggedCount}</p>
              <p className="text-xs text-amber-600">
                Already tagged with &quot;{state.sourceForm}&quot;
              </p>
            </div>
            <div className="text-center p-3 bg-blue-50 rounded-lg">
              <p className="text-xl font-bold text-blue-700">
                {existsNoTagCount}
              </p>
              <p className="text-xs text-blue-600">
                In vTiger, missing this tag
              </p>
            </div>
            <div className="text-center p-3 bg-gray-50 rounded-lg">
              <p className="text-xl font-bold text-gray-700">{newCount}</p>
              <p className="text-xs text-gray-500">New contacts</p>
            </div>
          </div>
        )}

        <div className="overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="text-left py-2 px-3 font-medium text-gray-700">
                  Row
                </th>
                <th className="text-left py-2 px-3 font-medium text-gray-700">
                  Name
                </th>
                <th className="text-left py-2 px-3 font-medium text-gray-700">
                  Email
                </th>
                <th className="text-left py-2 px-3 font-medium text-gray-700">
                  Organisation
                </th>
                {vtLookup && (
                  <th className="text-left py-2 px-3 font-medium text-gray-700">
                    vTiger Status
                  </th>
                )}
                <th className="text-left py-2 px-3 font-medium text-gray-700">
                  Details
                </th>
              </tr>
            </thead>
            <tbody>
              {bodies.map(({ row, body }) => {
                const orgField =
                  body.school_name_other ||
                  body.workplace_name_other ||
                  body.earlyyears_name_other ||
                  "—";
                const vt =
                  vtLookup && body.contact_email
                    ? vtLookup[body.contact_email]
                    : null;

                let vtBadge = null;
                if (vt) {
                  if (vt.has_tag) {
                    vtBadge = (
                      <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                        Already tagged
                      </span>
                    );
                  } else if (vt.found) {
                    vtBadge = (
                      <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                        Exists, no tag
                      </span>
                    );
                  } else {
                    vtBadge = (
                      <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                        New
                      </span>
                    );
                  }
                }

                return (
                  <>
                    <tr
                      key={row}
                      className={`border-b border-gray-100 hover:bg-gray-50 ${
                        vt?.has_tag ? "opacity-50" : ""
                      }`}
                    >
                      <td className="py-2 px-3 text-gray-500">{row}</td>
                      <td className="py-2 px-3 text-gray-900">
                        {body.contact_first_name} {body.contact_last_name}
                      </td>
                      <td className="py-2 px-3 text-gray-600">
                        {body.contact_email}
                      </td>
                      <td className="py-2 px-3 text-gray-600">{orgField}</td>
                      {vtLookup && (
                        <td className="py-2 px-3">{vtBadge || "—"}</td>
                      )}
                      <td className="py-2 px-3">
                        <button
                          onClick={() =>
                            setExpandedRow(expandedRow === row ? null : row)
                          }
                          className="text-blue-600 hover:text-blue-800 text-xs"
                        >
                          {expandedRow === row ? "Hide" : "Show"} JSON
                        </button>
                      </td>
                    </tr>
                    {expandedRow === row && (
                      <tr key={`${row}-detail`}>
                        <td
                          colSpan={vtLookup ? 6 : 5}
                          className="bg-gray-50 px-3 py-2 space-y-3"
                        >
                          <pre className="text-xs text-gray-700 whitespace-pre-wrap">
                            {JSON.stringify(body, null, 2)}
                          </pre>
                          {vt?.found && (
                            <div>
                              <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                vTiger Contact Record
                              </h4>
                              <pre className="text-xs bg-blue-950 text-blue-100 rounded-md p-3 overflow-x-auto whitespace-pre-wrap">
                                {JSON.stringify(vt.contacts, null, 2)}
                              </pre>
                            </div>
                          )}
                        </td>
                      </tr>
                    )}
                  </>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* Action buttons */}
      <div className="flex justify-between items-center">
        <button
          onClick={prev}
          className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900"
        >
          &larr; Back
        </button>
        <div className="flex gap-3">
          <button
            onClick={() => startImport("test", 1, false)}
            className="px-4 py-2 rounded-md text-sm font-medium border border-blue-300 text-blue-700 hover:bg-blue-50 transition-colors"
          >
            Test Run (1 row)
          </button>
          <button
            onClick={() => startImport("test", 2, false)}
            className="px-4 py-2 rounded-md text-sm font-medium border border-blue-300 text-blue-700 hover:bg-blue-50 transition-colors"
          >
            Test Run (2 rows)
          </button>
          <button
            onClick={() => startImport("test", 5, false)}
            className="px-4 py-2 rounded-md text-sm font-medium border border-blue-300 text-blue-700 hover:bg-blue-50 transition-colors"
          >
            Test Run (5 rows)
          </button>
          <button
            onClick={() => startImport("full", 0, false)}
            className="px-4 py-2 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors"
          >
            Run All ({bodies.length} rows)
          </button>
          {vtLookup && taggedCount > 0 && (
            <button
              onClick={() => startImport("full", 0, true)}
              className="px-4 py-2 rounded-md text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition-colors"
            >
              Run New Only ({importableCount} rows)
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
