# Vial & carton label spec — prepress pack 001

Answers a supplier's three questions for the first label order: design/template,
dimensions, and quantity.

- **[label-spec.html](label-spec.html)** — the whole pack. Artwork proofs at 100 %,
  die-lines, print spec, a live quantity worksheet, and a copy-paste reply for the
  supplier. Published at
  https://claude.ai/code/artifact/7800d579-4098-4142-8d07-d0c5a5dd3ede
  (republish that file to update the same URL).

## What it covers

Five SKUs, 10 vials per kit — Retatrutide 10 mg, SNAP-8 10 mg, TB-500 5 mg,
Tesamorelin 5 mg, Glutathione 600 mg — laid out on the existing
`mock_E_minimal` template.

Three dies:

| Die | Trim | For |
|-----|------|-----|
| A | 42 × 20 mm | 2R vial (Ø16 × 35 mm) — the four small SKUs |
| B | 64 × 28 mm | 10R vial (Ø24 × 45 mm) — Glutathione |
| C | 75 × 45 mm | 10-vial carton front face — all five |

Vial labels are a partial wrap. They stop short of full circumference so the
lyophilized cake stays visible, and must not overlap themselves.

## Open before anything is tooled

1. **Vial outside diameter for the four small SKUs.** If they are 10R rather than
   2R, Die A disappears and all five vials run on Die B at full template — one
   fewer die and no compressions.
2. **Quantity.** The worksheet defaults to 100 kits per SKU as a pilot assumption.
3. Typeface, exact violet and charcoal values, and the SKU category codes are
   approximated from the mock PNG — pull them from the source design file.
4. Every CAS number and the ≥ 99.0 % purity figure need checking against each CoA.

Full list in §8 of the pack.

## Template deviations

The mock_E layout needs roughly 62 mm of width to hold its three batch fields at
the 5 pt floor. Die C runs it complete; Die B drops the COA line; Die A also drops
the LABORATORIES sublabel and shortens the SKU to product + strength. Documented
per-die in §1.
