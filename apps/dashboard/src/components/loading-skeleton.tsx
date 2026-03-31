'use client';

export function LoadingSkeleton() {
  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      {/* Metric cards skeleton */}
      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        {[1, 2, 3].map((i) => (
          <div key={i} className="rounded-xl border border-border/50 bg-card p-5">
            <div className="skeleton h-3 w-24 mb-4" />
            <div className="skeleton h-8 w-20" />
          </div>
        ))}
      </div>
      {/* Chart skeleton */}
      <div className="rounded-xl border border-border/50 bg-card p-5">
        <div className="skeleton h-3 w-40 mb-6" />
        <div className="skeleton h-[250px] w-full" />
      </div>
      {/* Bottom charts skeleton */}
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {[1, 2].map((i) => (
          <div key={i} className="rounded-xl border border-border/50 bg-card p-5">
            <div className="skeleton h-3 w-36 mb-6" />
            <div className="skeleton h-[200px] w-full" />
          </div>
        ))}
      </div>
    </div>
  );
}
