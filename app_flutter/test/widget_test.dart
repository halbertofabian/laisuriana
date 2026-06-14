import 'package:flutter_test/flutter_test.dart';

import 'package:app_flutter/app/app.dart';

void main() {
  testWidgets('shows login entrypoint', (tester) async {
    await tester.pumpWidget(const LasurianaApp());

    expect(find.text('Entrar al demo UX'), findsOneWidget);
    expect(
      find.text('Venta de piso,\nlista para moverse contigo.'),
      findsOneWidget,
    );
  });
}
