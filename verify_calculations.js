// Verify statistical computations used in the dashboard.
// This ensures our Pearson r and Paired t-test CDF calculations are mathematically correct.

function pearsonCorrelation(pairs, keyFn) {
  const N = pairs.length;
  if (N < 2) return null;

  let sumX = 0, sumY = 0;
  let sumXSq = 0, sumYSq = 0;
  let sumXY = 0;

  for (let i = 0; i < N; i++) {
    const valX = keyFn(pairs[i].e1);
    const valY = keyFn(pairs[i].e2);
    sumX += valX;
    sumY += valY;
    sumXSq += valX * valX;
    sumYSq += valY * valY;
    sumXY += valX * valY;
  }

  const num = N * sumXY - sumX * sumY;
  const den = Math.sqrt((N * sumXSq - sumX * sumX) * (N * sumYSq - sumY * sumY));
  if (den === 0) return 0;
  return num / den;
}

function normalCDF(z) {
  const t = 1 / (1 + 0.2316419 * Math.abs(z));
  const d = 0.39894228 * Math.exp(-z * z / 2);
  const prob = d * t * (0.31938153 + t * (-0.356563782 + t * (1.781477937 + t * (-1.821255978 + 1.330274429 * t))));
  if (z >= 0) return 1 - prob;
  return prob;
}

function calculateOneTailedPValue(t, df) {
  if (df <= 0) return 1;
  const x = t;
  const n = df;
  if (n === 1) {
    return 0.5 - Math.atan(x) / Math.PI;
  }
  const d1 = 1 / (4 * n);
  const d2 = 5 / (96 * n * n);
  const z = x * Math.sqrt(1 - d1 + d2) / Math.sqrt(1 + x * x / (2 * n));
  return 1 - normalCDF(z);
}

// Test cases:
// 1. Pearson correlation test
// Let's create pairs of scores from Expert 1 and Expert 2
const expertPairs = [
  { e1: { total_score: 40 }, e2: { total_score: 42 } },
  { e1: { total_score: 45 }, e2: { total_score: 48 } },
  { e1: { total_score: 30 }, e2: { total_score: 32 } },
  { e1: { total_score: 55 }, e2: { total_score: 52 } },
  { e1: { total_score: 50 }, e2: { total_score: 51 } }
];

const r = pearsonCorrelation(expertPairs, e => e.total_score);
console.log("Calculated Pearson r:", r);
console.log("Expected Pearson r: ~0.9772");

// 2. Paired t-test test
const preScores =  [30, 35, 40, 45, 38];
const postScores = [42, 48, 51, 52, 49];

const N = 5;
const meanPre = preScores.reduce((a,b)=>a+b, 0)/N;
const meanPost = postScores.reduce((a,b)=>a+b, 0)/N;
const diffs = postScores.map((val, idx) => val - preScores[idx]);
const meanDiff = diffs.reduce((a,b)=>a+b,0)/N;
const sqDiffSum = diffs.reduce((sum, d) => sum + Math.pow(d - meanDiff, 2), 0);
const sdDiff = Math.sqrt(sqDiffSum / (N - 1));
const seDiff = sdDiff / Math.sqrt(N);
const tStat = meanDiff / seDiff;
const df = N - 1;
const pValue = calculateOneTailedPValue(tStat, df);

console.log("\nPaired t-test calculation:");
console.log("Mean Pre:", meanPre);
console.log("Mean Post:", meanPost);
console.log("Mean Diff:", meanDiff);
console.log("SD Diff:", sdDiff);
console.log("SE Diff:", seDiff);
console.log("t-statistic:", tStat);
console.log("p-value:", pValue);
