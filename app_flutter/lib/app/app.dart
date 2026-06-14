import 'package:flutter/material.dart';

import 'navigation/app_shell.dart';
import 'theme/app_theme.dart';
import '../features/auth/login_screen.dart';

class LasurianaApp extends StatefulWidget {
  const LasurianaApp({super.key});

  @override
  State<LasurianaApp> createState() => _LasurianaAppState();
}

class _LasurianaAppState extends State<LasurianaApp> {
  bool _authenticated = false;

  void _handleLogin() {
    setState(() {
      _authenticated = true;
    });
  }

  void _handleLogout() {
    setState(() {
      _authenticated = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'La Suriana Piso',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.theme,
      home: _authenticated
          ? AppShell(onLogout: _handleLogout)
          : LoginScreen(onLogin: _handleLogin),
    );
  }
}
