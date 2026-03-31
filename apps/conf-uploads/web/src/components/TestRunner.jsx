import { useState } from "react";

export default function TestRunner() {
  const [results, setResults] = useState(null);
  const [loading, setLoading] = useState(false);
  const [showOutput, setShowOutput] = useState(false);

  async function runTests() {
    setLoading(true);
    setResults(null);
    try {
      const res = await fetch("/api/tests", { method: "POST" });
      const data = await res.json();
      setResults(data);
    } catch (e) {
      setResults({ error: e.message });
    } finally {
      setLoading(false);
    }
  }

  const allPassed = results && results.exit_code === 0;

  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-medium text-gray-900">Test Suite</h2>
        <button
          onClick={runTests}
          disabled={loading}
          className={`px-4 py-2 rounded-md text-sm font-medium text-white transition-colors ${
            loading
              ? "bg-gray-400 cursor-not-allowed"
              : "bg-blue-600 hover:bg-blue-700"
          }`}
        >
          {loading ? "Running..." : "Run Tests"}
        </button>
      </div>

      {results && results.error && (
        <p className="text-red-600 text-sm">{results.error}</p>
      )}

      {results && !results.error && (
        <>
          {/* Summary badges */}
          <div className="flex gap-3 mb-4">
            <span
              className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
                allPassed
                  ? "bg-green-100 text-green-800"
                  : "bg-red-100 text-red-800"
              }`}
            >
              {allPassed ? "All passed" : "Failures detected"}
            </span>
            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-50 text-green-700">
              {results.passed} passed
            </span>
            {results.failed > 0 && (
              <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-50 text-red-700">
                {results.failed} failed
              </span>
            )}
            {results.errors > 0 && (
              <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-50 text-amber-700">
                {results.errors} errors
              </span>
            )}
          </div>

          {/* Individual test results */}
          <div className="space-y-1 mb-4">
            {results.tests.map((t, i) => (
              <div
                key={i}
                className="flex items-center gap-2 text-sm font-mono"
              >
                <span
                  className={`w-2 h-2 rounded-full flex-shrink-0 ${
                    t.status === "PASSED"
                      ? "bg-green-500"
                      : t.status === "FAILED"
                        ? "bg-red-500"
                        : "bg-amber-500"
                  }`}
                />
                <span className="text-gray-700 truncate">{t.nodeid}</span>
                <span
                  className={`ml-auto text-xs flex-shrink-0 ${
                    t.status === "PASSED"
                      ? "text-green-600"
                      : t.status === "FAILED"
                        ? "text-red-600"
                        : "text-amber-600"
                  }`}
                >
                  {t.status}
                </span>
              </div>
            ))}
          </div>

          {/* Raw output toggle */}
          <button
            onClick={() => setShowOutput(!showOutput)}
            className="text-sm text-blue-600 hover:text-blue-800"
          >
            {showOutput ? "Hide" : "Show"} full output
          </button>
          {showOutput && (
            <pre className="mt-2 p-3 bg-gray-900 text-gray-100 rounded-md text-xs overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap">
              {results.output}
            </pre>
          )}
        </>
      )}
    </div>
  );
}
