import 'package:flutter/material.dart';

class AppTheme {
  static const Color _sand = Color(0xFFF6F0E7);
  static const Color _paper = Color(0xFFFFFCF7);
  static const Color _ink = Color(0xFF1F2937);
  static const Color _muted = Color(0xFF6B7280);
  static const Color _forest = Color(0xFF14532D);
  static const Color _forestSoft = Color(0xFFE6F4EA);
  static const Color _amber = Color(0xFFC67C2E);
  static const Color _card = Color(0xFFFFFFFF);
  static const Color _stroke = Color(0xFFE7DED2);

  static ThemeData get theme {
    final base = ThemeData(useMaterial3: true);

    return base.copyWith(
      scaffoldBackgroundColor: _sand,
      colorScheme: ColorScheme.fromSeed(
        seedColor: _forest,
        brightness: Brightness.light,
        primary: _forest,
        secondary: _amber,
        surface: _paper,
      ),
      textTheme: base.textTheme.copyWith(
        headlineLarge: const TextStyle(
          fontSize: 30,
          fontWeight: FontWeight.w800,
          color: _ink,
          letterSpacing: -0.8,
        ),
        headlineSmall: const TextStyle(
          fontSize: 24,
          fontWeight: FontWeight.w800,
          color: _ink,
        ),
        titleLarge: const TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.w700,
          color: _ink,
        ),
        titleMedium: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w700,
          color: _ink,
        ),
        bodyLarge: const TextStyle(fontSize: 16, color: _ink),
        bodyMedium: const TextStyle(fontSize: 14, color: _ink),
        bodySmall: const TextStyle(fontSize: 12, color: _muted),
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        foregroundColor: _ink,
      ),
      cardTheme: CardThemeData(
        color: _card,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(24),
          side: const BorderSide(color: _stroke),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: _card,
        hintStyle: const TextStyle(color: _muted),
        prefixIconColor: _muted,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 18,
          vertical: 18,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(20),
          borderSide: const BorderSide(color: _stroke),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(20),
          borderSide: const BorderSide(color: _stroke),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(20),
          borderSide: const BorderSide(color: _forest, width: 1.4),
        ),
      ),
      chipTheme: base.chipTheme.copyWith(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
        side: const BorderSide(color: _stroke),
        backgroundColor: _paper,
        labelStyle: const TextStyle(color: _ink, fontWeight: FontWeight.w600),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: _card,
        selectedItemColor: _forest,
        unselectedItemColor: _muted,
        type: BottomNavigationBarType.fixed,
        showUnselectedLabels: true,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: _forest,
          foregroundColor: Colors.white,
          elevation: 0,
          minimumSize: const Size.fromHeight(56),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(52),
          side: const BorderSide(color: _stroke),
          foregroundColor: _ink,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),
        ),
      ),
      dividerColor: _stroke,
    );
  }

  static const Color ink = _ink;
  static const Color muted = _muted;
  static const Color forest = _forest;
  static const Color forestSoft = _forestSoft;
  static const Color amber = _amber;
  static const Color paper = _paper;
  static const Color sand = _sand;
  static const Color stroke = _stroke;
}
