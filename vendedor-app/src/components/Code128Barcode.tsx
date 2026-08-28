import JsBarcode from 'jsbarcode';
import { useEffect, useRef } from 'react';

export function Code128Barcode({ value }: { value: string }) {
  const barcodeRef = useRef<SVGSVGElement>(null);

  useEffect(() => {
    if (!barcodeRef.current || !value.trim()) return;

    JsBarcode(barcodeRef.current, value, {
      format: 'CODE128',
      displayValue: false,
      background: 'transparent',
      lineColor: '#172230',
      width: 2,
      height: 54,
      margin: 0,
    });
  }, [value]);

  return (
    <div className="barcode" role="img" aria-label={`Código de barras del folio ${value}`}>
      <svg ref={barcodeRef} aria-hidden="true" />
    </div>
  );
}
