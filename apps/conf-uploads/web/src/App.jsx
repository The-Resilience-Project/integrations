import { useState } from "react";
import FileUpload from "./components/FileUpload";
import ColumnMapping from "./components/ColumnMapping";
import ImportConfig from "./components/ImportConfig";
import ProofReview from "./components/ProofReview";
import ImportResults from "./components/ImportResults";
import TestRunner from "./components/TestRunner";

const STEPS = [
  { label: "Upload", component: FileUpload },
  { label: "Mapping", component: ColumnMapping },
  { label: "Configure", component: ImportConfig },
  { label: "Proof", component: ProofReview },
  { label: "Import", component: ImportResults },
];

export default function App() {
  const [step, setStep] = useState(0);
  const [showTests, setShowTests] = useState(false);
  const [state, setState] = useState({
    filePath: null,
    filename: null,
    headers: [],
    columnMapping: {},
    previewRows: [],
    totalRows: 0,
    serviceType: "School",
    sourceForm: "",
    endpointType: "Prize Pack",
    proofBodies: [],
    importResults: null,
  });

  const update = (patch) => setState((prev) => ({ ...prev, ...patch }));
  const next = () => setStep((s) => Math.min(s + 1, STEPS.length - 1));
  const prev = () => setStep((s) => Math.max(s - 1, 0));
  const goTo = (s) => setStep(s);

  const StepComponent = STEPS[step].component;

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-5xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between">
            <h1 className="text-xl font-semibold text-gray-900">
              Conference Uploads
            </h1>
            <button
              onClick={() => setShowTests(!showTests)}
              className="px-3 py-1 rounded-md text-sm font-medium border border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors"
            >
              {showTests ? "Hide Tests" : "Tests"}
            </button>
          </div>
        </div>
      </header>

      {/* Step indicators */}
      <nav className="max-w-5xl mx-auto px-6 py-4">
        <ol className="flex items-center gap-2">
          {STEPS.map((s, i) => (
            <li key={s.label} className="flex items-center gap-2">
              <button
                onClick={() => i < step && goTo(i)}
                disabled={i > step}
                className={`px-3 py-1 rounded-full text-sm font-medium transition-colors ${
                  i === step
                    ? "bg-blue-600 text-white"
                    : i < step
                      ? "bg-blue-100 text-blue-700 hover:bg-blue-200 cursor-pointer"
                      : "bg-gray-200 text-gray-400 cursor-not-allowed"
                }`}
              >
                {i + 1}. {s.label}
              </button>
              {i < STEPS.length - 1 && (
                <span className="text-gray-300">&rarr;</span>
              )}
            </li>
          ))}
        </ol>
      </nav>

      {/* Test runner panel */}
      {showTests && (
        <div className="max-w-5xl mx-auto px-6 pb-4">
          <TestRunner />
        </div>
      )}

      {/* Step content */}
      <main className="max-w-5xl mx-auto px-6 pb-12">
        <StepComponent
          state={state}
          update={update}
          next={next}
          prev={prev}
          goTo={goTo}
        />
      </main>
    </div>
  );
}
