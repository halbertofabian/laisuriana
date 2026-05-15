import { StatusBar } from "expo-status-bar";
import { useEffect, useMemo, useRef, useState } from "react";
import { CameraView, useCameraPermissions } from "expo-camera";
import { MaterialCommunityIcons } from "@expo/vector-icons";
import {
  ActivityIndicator,
  Alert,
  FlatList,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  SafeAreaView,
  ScrollView,
  StatusBar as RNStatusBar,
  StyleSheet,
  Text,
  TextInput,
  View
} from "react-native";
import { clearSession, getSession, saveSession } from "./src/storage/session";
import type {
  AlmacenOption,
  Session,
  SucursalOption,
  UsuarioSugerido
} from "./src/types/auth";
import {
  buscarUsuarios,
  cargarAlmacenes,
  cargarSucursales,
  login
} from "./src/services/auth";
import {
  buscarProductosPedido,
  guardarPedidoPiso,
  listarPedidosPiso
} from "./src/services/pedidos";
import type {
  PartidaPedido,
  PedidoRow,
  ProductoSugerencia
} from "./src/types/pedidos";

export default function App() {
  const passwordRef = useRef<TextInput>(null);

  const [usuario, setUsuario] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [booting, setBooting] = useState(true);
  const [session, setSession] = useState<Session | null>(null);

  const [sugerencias, setSugerencias] = useState<UsuarioSugerido[]>([]);
  const [sugerenciasCache, setSugerenciasCache] = useState<UsuarioSugerido[]>([]);
  const [buscandoUsuarios, setBuscandoUsuarios] = useState(false);
  const [mostrarSelectUsuarios, setMostrarSelectUsuarios] = useState(false);
  const [errorBusquedaUsuarios, setErrorBusquedaUsuarios] = useState<string>("");
  const [bloquearBusquedaUsuario, setBloquearBusquedaUsuario] = useState(false);

  const [mostrarModalContexto, setMostrarModalContexto] = useState(false);
  const [sucursales, setSucursales] = useState<SucursalOption[]>([]);
  const [almacenes, setAlmacenes] = useState<AlmacenOption[]>([]);
  const [sucursalSeleccionada, setSucursalSeleccionada] = useState<SucursalOption | null>(null);
  const [almacenSeleccionado, setAlmacenSeleccionado] = useState<AlmacenOption | null>(null);
  const [cargandoCatalogos, setCargandoCatalogos] = useState(false);
  const [selectorActivo, setSelectorActivo] = useState<"sucursal" | "almacen" | null>(null);

  const [pantallaPedidoActiva, setPantallaPedidoActiva] = useState(false);
  const [buscarProducto, setBuscarProducto] = useState("");
  const [sugerenciasProducto, setSugerenciasProducto] = useState<ProductoSugerencia[]>([]);
  const [mostrarSugerenciasProducto, setMostrarSugerenciasProducto] = useState(false);
  const [partidasPedido, setPartidasPedido] = useState<PartidaPedido[]>([]);
  const [observacionesPedido, setObservacionesPedido] = useState("");
  const [guardandoPedido, setGuardandoPedido] = useState(false);
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const [mostrarScanner, setMostrarScanner] = useState(false);
  const [scannerBusy, setScannerBusy] = useState(false);
  const [tabActiva, setTabActiva] = useState<"inicio" | "perfil">("inicio");
  const [pendientesCobro, setPendientesCobro] = useState<PedidoRow[]>([]);
  const [cargandoPendientes, setCargandoPendientes] = useState(false);

  useEffect(() => {
    void (async () => {
      const stored = await getSession();
      setSession(stored);
      setBooting(false);
    })();
  }, []);

  useEffect(() => {
    const query = usuario.trim();
    if (bloquearBusquedaUsuario) {
      setBuscandoUsuarios(false);
      return;
    }
    if (query.length < 1) {
      setSugerencias([]);
      setMostrarSelectUsuarios(false);
      setErrorBusquedaUsuarios("");
      return;
    }

    setMostrarSelectUsuarios(true);
    setErrorBusquedaUsuarios("");

    if (sugerenciasCache.length > 0) {
      const filtered = sugerenciasCache.filter((u) =>
        `${u.usr_usuario} ${u.usr_nombre}`.toLowerCase().includes(query.toLowerCase())
      );
      setSugerencias(filtered);
    }

    if (query.length < 2) return;

    const timer = setTimeout(() => {
      setBuscandoUsuarios(true);
      void buscarUsuarios(query)
        .then((data) => {
          setSugerencias(data);
          setSugerenciasCache((prev) => {
            const map = new Map<string, UsuarioSugerido>();
            [...prev, ...data].forEach((item) => map.set(item.usr_usuario, item));
            return Array.from(map.values());
          });
        })
        .catch((error) => {
          setSugerencias([]);
          setErrorBusquedaUsuarios(
            error instanceof Error ? error.message : "No se pudo consultar usuarios."
          );
        })
        .finally(() => setBuscandoUsuarios(false));
    }, 250);

    return () => clearTimeout(timer);
  }, [usuario, sugerenciasCache, bloquearBusquedaUsuario]);

  const onChangeUsuario = (value: string) => {
    setBloquearBusquedaUsuario(false);
    setUsuario(value);
  };

  const onSubmit = async () => {
    if (!usuario.trim() || !password.trim()) {
      Alert.alert("Faltan datos", "Captura usuario y contraseña.");
      return;
    }

    setLoading(true);
    try {
      const nextSession = await login({ usuario: usuario.trim(), password });
      await saveSession(nextSession);
      setSession(nextSession);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Error de autenticación";
      Alert.alert("No fue posible iniciar sesión", message);
    } finally {
      setLoading(false);
    }
  };

  const onLogout = async () => {
    await clearSession();
    setSession(null);
    setPantallaPedidoActiva(false);
    setSucursalSeleccionada(null);
    setAlmacenSeleccionado(null);
  };

  const abrirModalContexto = async () => {
    setMostrarModalContexto(true);
    setCargandoCatalogos(true);
    try {
      const data = await cargarSucursales();
      setSucursales(data);
    } catch (error) {
      Alert.alert("Error", error instanceof Error ? error.message : "No se pudieron cargar sucursales.");
    } finally {
      setCargandoCatalogos(false);
    }
  };

  const seleccionarSucursal = async (item: SucursalOption) => {
    setSucursalSeleccionada(item);
    setAlmacenSeleccionado(null);
    setSelectorActivo(null);
    setCargandoCatalogos(true);
    try {
      const data = await cargarAlmacenes(item.scl_id);
      setAlmacenes(data);
    } catch (error) {
      Alert.alert("Error", error instanceof Error ? error.message : "No se pudieron cargar almacenes.");
    } finally {
      setCargandoCatalogos(false);
    }
  };

  const confirmarContextoPedido = () => {
    if (!sucursalSeleccionada || !almacenSeleccionado) {
      Alert.alert("Faltan datos", "Selecciona sucursal y almacén.");
      return;
    }
    setMostrarModalContexto(false);
    setPantallaPedidoActiva(true);
  };

  const cargarPendientesCobro = async () => {
    setCargandoPendientes(true);
    try {
      const rows = await listarPedidosPiso({ pdp_estatus: "pendiente_cobro" });
      setPendientesCobro(rows);
    } catch (error) {
      Alert.alert(
        "Error",
        error instanceof Error ? error.message : "No se pudieron cargar pedidos pendientes."
      );
    } finally {
      setCargandoPendientes(false);
    }
  };

  useEffect(() => {
    if (!session || pantallaPedidoActiva || tabActiva !== "inicio") return;
    void cargarPendientesCobro();
  }, [session, pantallaPedidoActiva, tabActiva]);

  const totalPedido = useMemo(
    () => partidasPedido.reduce((s, p) => s + p.cantidad * p.precio, 0),
    [partidasPedido]
  );

  useEffect(() => {
    const q = buscarProducto.trim();
    if (!pantallaPedidoActiva || q.length < 2) {
      setSugerenciasProducto([]);
      setMostrarSugerenciasProducto(false);
      return;
    }
    const timer = setTimeout(() => {
      void buscarProductosPedido(q).then((rows) => {
        setSugerenciasProducto(rows);
        setMostrarSugerenciasProducto(rows.length > 0);
      });
    }, 220);
    return () => clearTimeout(timer);
  }, [buscarProducto, pantallaPedidoActiva]);

  const agregarPartida = (item: ProductoSugerencia) => {
    const skuId = Number(item.psk_id);
    const idx = partidasPedido.findIndex((p) => p.ppd_psk_id === skuId);
    if (idx >= 0) {
      setPartidasPedido((prev) =>
        prev.map((p, i) => (i === idx ? { ...p, cantidad: p.cantidad + 1 } : p))
      );
    } else {
      const nombre = item.psk_nombre || item.producto?.prd_nombre || item.psk_codigo;
      setPartidasPedido((prev) => [
        ...prev,
        {
          ppd_psk_id: skuId,
          sku: item.psk_codigo,
          nombre: String(nombre || item.psk_codigo),
          cantidad: 1,
          precio: Number(item.psk_precio || 0)
        }
      ]);
    }
    setBuscarProducto("");
    setSugerenciasProducto([]);
    setMostrarSugerenciasProducto(false);
  };

  const cambiarCantidad = (skuId: number, delta: number) => {
    setPartidasPedido((prev) =>
      prev.map((item) => {
        if (item.ppd_psk_id !== skuId) return item;
        const next = Math.max(1, item.cantidad + delta);
        return { ...item, cantidad: next };
      })
    );
  };

  const quitarItem = (skuId: number) => {
    setPartidasPedido((prev) => prev.filter((item) => item.ppd_psk_id !== skuId));
  };

  const abrirScanner = async () => {
    if (!cameraPermission?.granted) {
      const resp = await requestCameraPermission();
      if (!resp.granted) {
        Alert.alert("Permiso requerido", "Activa el permiso de cámara para escanear códigos.");
        return;
      }
    }
    setMostrarScanner(true);
  };

  const onBarcodeScanned = async (code: string) => {
    if (scannerBusy) return;
    setScannerBusy(true);
    try {
      const results = await buscarProductosPedido(code);
      if (!results.length) {
        Alert.alert("Sin coincidencias", `No se encontró producto para: ${code}`);
        return;
      }

      const exact =
        results.find((r) => (r.psk_codigo_barras || "").trim() === code.trim()) || results[0];
      agregarPartida(exact);
      setMostrarScanner(false);
    } catch (error) {
      Alert.alert(
        "Error al escanear",
        error instanceof Error ? error.message : "No se pudo consultar producto."
      );
    } finally {
      setTimeout(() => setScannerBusy(false), 400);
    }
  };

  const generarPedido = async () => {
    if (!sucursalSeleccionada || !almacenSeleccionado) {
      Alert.alert("Faltan datos", "Selecciona sucursal y almacén.");
      return;
    }
    if (!partidasPedido.length) {
      Alert.alert("Faltan partidas", "Agrega al menos un producto al pedido.");
      return;
    }

    setGuardandoPedido(true);
    try {
      const resp = await guardarPedidoPiso({
        pdp_scl_id: sucursalSeleccionada.scl_id,
        pdp_alm_id: almacenSeleccionado.alm_id,
        pdp_observaciones: observacionesPedido,
        partidas: partidasPedido
      });
      Alert.alert("Pedido generado", resp.pdp_folio);
      setPartidasPedido([]);
      setObservacionesPedido("");
    } catch (error) {
      Alert.alert("No fue posible guardar", error instanceof Error ? error.message : "Error");
    } finally {
      setGuardandoPedido(false);
    }
  };

  if (booting) {
    return (
      <SafeAreaView style={styles.centered}>
        <ActivityIndicator size="large" color="#2563EB" />
      </SafeAreaView>
    );
  }

  if (!session) {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="dark" />
        <KeyboardAvoidingView
          behavior={Platform.OS === "ios" ? "padding" : undefined}
          style={styles.keyboardArea}
        >
          <View style={styles.shell}>
            <View style={styles.brandRow}>
              <View style={styles.brandIcon}>
                <MaterialCommunityIcons name="storefront-outline" size={18} color="#fff" />
              </View>
              <Text style={styles.brandName}>La iSuriana Retail</Text>
            </View>
            <View style={styles.card}>
              <Text style={styles.title}>Iniciar sesión</Text>
              <Text style={styles.subtitle}>Ingresa con tu usuario y contraseña para continuar.</Text>

              <Text style={styles.label}>Usuario</Text>
              <TextInput
                autoCapitalize="none"
                autoCorrect={false}
                autoComplete="username"
                style={styles.input}
                value={usuario}
                onChangeText={onChangeUsuario}
                onFocus={() => setMostrarSelectUsuarios(true)}
                placeholder="Escribe tu usuario..."
                placeholderTextColor="#64748B"
              />
              <Text style={styles.hint}>Escribe para filtrar usuarios.</Text>
              <View style={styles.loginStatusRow}>
                {errorBusquedaUsuarios ? <Text style={styles.errorHint}>{errorBusquedaUsuarios}</Text> : null}
              </View>

              {mostrarSelectUsuarios && sugerencias.length > 0 ? (
                <View style={styles.sugerenciasBox}>
                  <FlatList
                    data={sugerencias}
                    keyExtractor={(item) => item.usr_usuario}
                    keyboardShouldPersistTaps="handled"
                    renderItem={({ item }) => (
                      <Pressable
                        style={styles.sugerenciaItem}
                        onPress={() => {
                          setBloquearBusquedaUsuario(true);
                          setUsuario(item.usr_usuario);
                          setSugerencias([]);
                          setMostrarSelectUsuarios(false);
                          passwordRef.current?.focus();
                        }}
                      >
                        <Text style={styles.sugerenciaUser}>{item.usr_usuario}</Text>
                        <Text style={styles.sugerenciaName}>{item.usr_nombre}</Text>
                      </Pressable>
                    )}
                  />
                </View>
              ) : null}

              <Text style={styles.label}>Contraseña</Text>
              <TextInput
                ref={passwordRef}
                secureTextEntry
                autoCapitalize="none"
                autoComplete="password"
                style={styles.input}
                value={password}
                onChangeText={setPassword}
                onFocus={() => setMostrarSelectUsuarios(false)}
                placeholder="********"
                placeholderTextColor="#64748B"
              />

              <Pressable style={[styles.loginBtn, loading && styles.loginBtnDisabled]} onPress={onSubmit} disabled={loading}>
                {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.loginBtnText}>Entrar al sistema</Text>}
              </Pressable>
            </View>
          </View>
        </KeyboardAvoidingView>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />

      <View style={styles.topNavbar}>
        <Text style={styles.topNavbarTitle}>La I. Suriana</Text>
      </View>

      {!pantallaPedidoActiva ? (
        <View style={styles.homeContent}>
          {tabActiva === "inicio" ? (
            <>
              <View style={styles.pendingHeader}>
                <Text style={styles.pendingTitle}>Pedidos pendientes de cobro</Text>
                <Pressable style={styles.refreshBtn} onPress={() => void cargarPendientesCobro()}>
                  <MaterialCommunityIcons name="refresh" size={16} color="#2563EB" />
                  <Text style={styles.refreshBtnText}>Actualizar</Text>
                </Pressable>
              </View>
              {cargandoPendientes ? (
                <View style={styles.pendingLoading}>
                  <ActivityIndicator color="#2563EB" />
                </View>
              ) : pendientesCobro.length === 0 ? (
                <View style={styles.emptyStateCard}>
                  <Text style={styles.hint}>No hay pedidos en estado Pendiente Cobro.</Text>
                </View>
              ) : (
                <ScrollView contentContainerStyle={{ paddingBottom: 90 }}>
                  {pendientesCobro.map((r) => (
                    <View key={String(r.pdp_id)} style={styles.pendingCard}>
                      <View style={styles.pendingTopRow}>
                        <Text style={styles.pendingFolio}>{r.pdp_folio}</Text>
                        <View style={styles.pendingBadge}>
                          <Text style={styles.pendingBadgeText}>Pendiente Cobro</Text>
                        </View>
                      </View>
                      <Text style={styles.pendingMeta}>{r.sucursal || "—"} / {r.almacen || "—"}</Text>
                      <Text style={styles.pendingMeta}>Vendedor: {r.vendedor || "—"}</Text>
                      <Text style={styles.pendingTotal}>${Number(r.pdp_total || 0).toFixed(2)}</Text>
                    </View>
                  ))}
                </ScrollView>
              )}
            </>
          ) : (
            <>
              <Text style={styles.welcomeTitle}>Perfil</Text>
              <Text style={styles.welcomeText}>Usuario: {session.usuario}</Text>
              <Pressable style={styles.logoutBtn} onPress={onLogout}>
                <Text style={styles.logoutBtnText}>Cerrar sesión</Text>
              </Pressable>
            </>
          )}
        </View>
      ) : (
        <View style={styles.pedidoScreen}>
          <View style={styles.searchHeader}>
            <TextInput
              style={styles.searchInput}
              value={buscarProducto}
              onChangeText={setBuscarProducto}
              placeholder="Buscar producto..."
              placeholderTextColor="#94A3B8"
              onFocus={() => setMostrarSugerenciasProducto(sugerenciasProducto.length > 0)}
            />
            <Pressable style={styles.scanBtn} onPress={abrirScanner}>
              <MaterialCommunityIcons name="barcode-scan" size={21} color="#fff" />
            </Pressable>
          </View>
          {mostrarSugerenciasProducto ? (
            <View style={styles.sugerenciasBox}>
              <FlatList
                data={sugerenciasProducto}
                keyExtractor={(item) => String(item.psk_id)}
                keyboardShouldPersistTaps="handled"
                renderItem={({ item }) => (
                  <Pressable style={styles.sugerenciaItem} onPress={() => agregarPartida(item)}>
                    <Text style={styles.sugerenciaUser}>
                      {item.psk_nombre || item.producto?.prd_nombre || item.psk_codigo}
                    </Text>
                    <Text style={styles.sugerenciaName}>
                      {item.psk_codigo} · ${Number(item.psk_precio || 0).toFixed(2)}
                    </Text>
                  </Pressable>
                )}
              />
            </View>
          ) : null}

          <ScrollView contentContainerStyle={styles.productList}>
            {partidasPedido.map((item) => (
              <View key={String(item.ppd_psk_id)} style={styles.productCard}>
                <Text style={styles.productName}>{item.nombre}</Text>
                <Text style={styles.productSku}>SKU: {item.sku}</Text>
                <View style={styles.productRow}>
                  <View style={styles.counterWrap}>
                    <Pressable style={styles.counterBtn} onPress={() => cambiarCantidad(item.ppd_psk_id, -1)}>
                      <Text style={styles.counterBtnText}>-</Text>
                    </Pressable>
                    <Text style={styles.counterValue}>{item.cantidad}</Text>
                    <Pressable style={styles.counterBtn} onPress={() => cambiarCantidad(item.ppd_psk_id, 1)}>
                      <Text style={styles.counterBtnText}>+</Text>
                    </Pressable>
                  </View>
                  <Text style={styles.productPrice}>${item.precio.toFixed(2)}</Text>
                  <Pressable style={styles.removeBtn} onPress={() => quitarItem(item.ppd_psk_id)}>
                    <MaterialCommunityIcons name="trash-can-outline" size={16} color="#EF4444" />
                  </Pressable>
                </View>
              </View>
            ))}
            {!partidasPedido.length ? (
              <View style={styles.emptyStateCard}>
                <Text style={styles.hint}>Busca un producto arriba para agregarlo al pedido.</Text>
              </View>
            ) : null}

            <Text style={styles.label}>Notas del pedido (opcional)</Text>
            <TextInput
              style={[styles.input, { minHeight: 72, textAlignVertical: "top" }]}
              multiline
              value={observacionesPedido}
              onChangeText={setObservacionesPedido}
              placeholder="Instrucciones especiales, referencias..."
              placeholderTextColor="#94A3B8"
            />

            <View style={styles.totalBar}>
              <Text style={styles.totalLabel}>Total del pedido</Text>
              <Text style={styles.totalAmount}>${totalPedido.toFixed(2)}</Text>
            </View>

            <Pressable
              style={[styles.continueBtn, guardandoPedido && styles.loginBtnDisabled]}
              onPress={generarPedido}
              disabled={guardandoPedido}
            >
              <Text style={styles.continueBtnText}>
                {guardandoPedido ? "Guardando..." : "Generar pedido"}
              </Text>
            </Pressable>
          </ScrollView>
        </View>
      )}

      <View style={styles.bottomNavbar}>
        <Pressable style={styles.bottomIconBtn} onPress={() => {
          setPantallaPedidoActiva(false);
          setTabActiva("inicio");
        }}>
          <MaterialCommunityIcons name="view-dashboard-outline" size={20} color="#475569" />
          <Text style={styles.bottomLabel}>Inicio</Text>
        </Pressable>
        <View style={styles.fabSpacer} />
        <Pressable style={styles.bottomIconBtn} onPress={() => {
          setPantallaPedidoActiva(false);
          setTabActiva("perfil");
        }}>
          <MaterialCommunityIcons name="account-circle-outline" size={20} color="#475569" />
          <Text style={styles.bottomLabel}>Perfil</Text>
        </Pressable>
      </View>

      <Pressable style={styles.fab} onPress={abrirModalContexto}>
        <MaterialCommunityIcons name="plus" size={34} color="#fff" />
      </Pressable>

      <Modal visible={mostrarModalContexto} transparent animationType="slide" onRequestClose={() => setMostrarModalContexto(false)}>
        <View style={styles.sheetOverlay}>
          <View style={styles.sheet}>
            <View style={styles.sheetGrabber} />
            <Text style={styles.sheetTitle}>Configurar pedido</Text>

            <Text style={styles.label}>Sucursal</Text>
            <Pressable style={styles.selectorInput} onPress={() => setSelectorActivo("sucursal")} disabled={cargandoCatalogos}>
              <Text style={sucursalSeleccionada ? styles.selectorText : styles.selectorPlaceholder}>
                {sucursalSeleccionada?.scl_nombre || "Selecciona sucursal"}
              </Text>
            </Pressable>

            <Text style={styles.label}>Almacén</Text>
            <Pressable
              style={styles.selectorInput}
              onPress={() => setSelectorActivo("almacen")}
              disabled={!sucursalSeleccionada || cargandoCatalogos}
            >
              <Text style={almacenSeleccionado ? styles.selectorText : styles.selectorPlaceholder}>
                {almacenSeleccionado?.alm_nombre || "Selecciona almacén"}
              </Text>
            </Pressable>

            {cargandoCatalogos ? <Text style={styles.hint}>Cargando catálogo...</Text> : null}

            <Pressable style={styles.continueBtn} onPress={confirmarContextoPedido}>
              <Text style={styles.continueBtnText}>Continuar</Text>
            </Pressable>
          </View>
        </View>
      </Modal>

      <Modal visible={selectorActivo !== null} transparent animationType="fade" onRequestClose={() => setSelectorActivo(null)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>
              {selectorActivo === "sucursal" ? "Seleccionar sucursal" : "Seleccionar almacén"}
            </Text>

            {selectorActivo === "sucursal" ? (
              <FlatList
                data={sucursales}
                keyExtractor={(item) => String(item.scl_id)}
                renderItem={({ item }) => (
                  <Pressable style={styles.optionItem} onPress={() => void seleccionarSucursal(item)}>
                    <Text style={styles.optionText}>{item.scl_nombre}</Text>
                  </Pressable>
                )}
                ListEmptyComponent={<Text style={styles.hint}>Sin sucursales disponibles.</Text>}
              />
            ) : (
              <FlatList
                data={almacenes}
                keyExtractor={(item) => String(item.alm_id)}
                renderItem={({ item }) => (
                  <Pressable
                    style={styles.optionItem}
                    onPress={() => {
                      setAlmacenSeleccionado(item);
                      setSelectorActivo(null);
                    }}
                  >
                    <Text style={styles.optionText}>{item.alm_nombre}</Text>
                  </Pressable>
                )}
                ListEmptyComponent={<Text style={styles.hint}>Sin almacenes disponibles.</Text>}
              />
            )}

            <Pressable style={styles.modalCloseBtn} onPress={() => setSelectorActivo(null)}>
              <Text style={styles.modalCloseText}>Cerrar</Text>
            </Pressable>
          </View>
        </View>
      </Modal>

      <Modal visible={mostrarScanner} animationType="slide" onRequestClose={() => setMostrarScanner(false)}>
        <SafeAreaView style={styles.scannerScreen}>
          <View style={styles.scannerHeader}>
            <Text style={styles.scannerTitle}>Escanear código</Text>
            <Pressable style={styles.modalCloseBtn} onPress={() => setMostrarScanner(false)}>
              <Text style={styles.modalCloseText}>Cerrar</Text>
            </Pressable>
          </View>
          <View style={styles.scannerBody}>
            {cameraPermission?.granted ? (
              <CameraView
                style={styles.cameraView}
                barcodeScannerSettings={{
                  barcodeTypes: ["ean13", "ean8", "code128", "code39", "upc_a", "upc_e", "itf14"]
                }}
                onBarcodeScanned={(event: { data: string }) => void onBarcodeScanned(event.data)}
              />
            ) : (
              <View style={styles.scannerFallback}>
                <Text style={styles.hint}>Sin permiso de cámara.</Text>
              </View>
            )}
            <View style={styles.scanGuide}>
              <Text style={styles.scanGuideText}>Apunta al código de barras del producto</Text>
            </View>
          </View>
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8FAFC" },
  centered: { flex: 1, alignItems: "center", justifyContent: "center", backgroundColor: "#F8FAFC" },
  keyboardArea: { flex: 1, justifyContent: "center", paddingHorizontal: 18 },
  shell: { width: "100%", maxWidth: 420, alignSelf: "center" },
  brandRow: { flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 10, marginBottom: 18 },
  brandIcon: { width: 36, height: 36, borderRadius: 10, backgroundColor: "#2563EB", alignItems: "center", justifyContent: "center" },
  brandIconText: { color: "#fff", fontWeight: "700", fontSize: 12 },
  brandName: { fontSize: 18, fontWeight: "700", color: "#0F172A" },
  card: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderRadius: 16,
    padding: 24,
    shadowColor: "#000",
    shadowOpacity: 0.06,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 6 },
    elevation: 3
  },
  title: { fontSize: 26, fontWeight: "700", color: "#0F172A" },
  subtitle: { marginTop: 6, marginBottom: 18, fontSize: 14, color: "#64748B" },
  label: { marginBottom: 6, marginTop: 10, fontSize: 13, fontWeight: "600", color: "#334155" },
  input: {
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 16,
    color: "#0F172A",
    backgroundColor: "#fff"
  },
  hint: { marginTop: 4, fontSize: 12, color: "#64748B" },
  errorHint: { marginTop: 4, fontSize: 12, color: "#DC2626" },
  loginStatusRow: {
    minHeight: 18,
    justifyContent: "center"
  },
  sugerenciasBox: { marginTop: 6, borderWidth: 1, borderColor: "#E2E8F0", borderRadius: 10, maxHeight: 140, backgroundColor: "#fff" },
  sugerenciaItem: { paddingHorizontal: 10, paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: "#EEF2F7" },
  sugerenciaUser: { fontSize: 14, fontWeight: "700", color: "#0F172A" },
  sugerenciaName: { fontSize: 12, color: "#64748B", marginTop: 2 },
  loginBtn: { marginTop: 20, backgroundColor: "#2563EB", borderRadius: 10, paddingVertical: 12, alignItems: "center" },
  loginBtnDisabled: { opacity: 0.65 },
  loginBtnText: { color: "#fff", fontSize: 15, fontWeight: "700" },

  topNavbar: {
    height: Platform.OS === "android" ? 58 + (RNStatusBar.currentHeight || 0) : 58,
    paddingTop: Platform.OS === "android" ? (RNStatusBar.currentHeight || 0) : 0,
    backgroundColor: "#FFFFFF",
    borderBottomWidth: 1,
    borderBottomColor: "#E2E8F0",
    alignItems: "center",
    justifyContent: "center"
  },
  topNavbarTitle: { fontSize: 22, fontWeight: "700", color: "#0F172A" },
  homeContent: { flex: 1, padding: 20 },
  welcomeTitle: { fontSize: 34, fontWeight: "700", color: "#0F172A" },
  welcomeText: { marginTop: 8, fontSize: 16, color: "#334155" },
  pendingHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10
  },
  pendingTitle: {
    fontSize: 22,
    fontWeight: "700",
    color: "#0F172A"
  },
  refreshBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    borderWidth: 1,
    borderColor: "#BFDBFE",
    backgroundColor: "#EFF6FF",
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 6
  },
  refreshBtnText: {
    color: "#2563EB",
    fontSize: 12,
    fontWeight: "700"
  },
  pendingLoading: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderRadius: 12,
    paddingVertical: 30,
    alignItems: "center"
  },
  pendingCard: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderRadius: 12,
    padding: 12,
    marginBottom: 10
  },
  pendingTopRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center"
  },
  pendingFolio: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1D4ED8"
  },
  pendingBadge: {
    backgroundColor: "rgba(245, 158, 11, 0.16)",
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 4
  },
  pendingBadgeText: {
    color: "#B45309",
    fontSize: 11,
    fontWeight: "700"
  },
  pendingMeta: {
    marginTop: 5,
    color: "#475569",
    fontSize: 12
  },
  pendingTotal: {
    marginTop: 8,
    color: "#0F172A",
    fontWeight: "800",
    fontSize: 18
  },
  logoutBtn: {
    marginTop: 18,
    borderWidth: 1,
    borderColor: "#2563EB",
    borderRadius: 10,
    paddingVertical: 10,
    alignItems: "center"
  },
  logoutBtnText: { color: "#2563EB", fontSize: 16, fontWeight: "700" },

  pedidoScreen: { flex: 1, paddingHorizontal: 14, paddingTop: 14, paddingBottom: 90 },
  searchHeader: { flexDirection: "row", gap: 10, alignItems: "center", marginBottom: 14 },
  searchInput: {
    flex: 0.8,
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 11,
    color: "#0F172A"
  },
  scanBtn: {
    flex: 0.2,
    height: 46,
    backgroundColor: "#2563EB",
    borderRadius: 12,
    alignItems: "center",
    justifyContent: "center"
  },
  scanBtnText: { color: "#fff", fontSize: 20 },
  productList: { paddingBottom: 20 },
  emptyStateCard: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderRadius: 12,
    padding: 12,
    marginBottom: 10
  },
  productCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    padding: 14,
    marginBottom: 10
  },
  productName: { fontSize: 16, fontWeight: "700", color: "#0F172A" },
  productSku: { marginTop: 4, fontSize: 13, color: "#64748B" },
  productRow: { marginTop: 12, flexDirection: "row", alignItems: "center", justifyContent: "space-between" },
  counterWrap: { flexDirection: "row", alignItems: "center", gap: 8 },
  counterBtn: {
    width: 34,
    height: 34,
    borderRadius: 8,
    backgroundColor: "#F1F5F9",
    alignItems: "center",
    justifyContent: "center"
  },
  counterBtnText: { fontSize: 20, color: "#334155", fontWeight: "700" },
  counterValue: { fontSize: 16, color: "#0F172A", minWidth: 18, textAlign: "center" },
  productPrice: { fontSize: 15, fontWeight: "700", color: "#0F172A" },
  removeBtn: {
    borderWidth: 1,
    borderColor: "#FCA5A5",
    borderRadius: 10,
    width: 36,
    height: 36,
    alignItems: "center",
    justifyContent: "center"
  },
  removeBtnText: { color: "#EF4444", fontWeight: "700" },
  totalBar: {
    marginTop: 10,
    backgroundColor: "#1E293B",
    borderRadius: 12,
    paddingVertical: 12,
    paddingHorizontal: 14,
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center"
  },
  totalLabel: { color: "#cbd5e1", fontSize: 12, fontWeight: "700", textTransform: "uppercase" },
  totalAmount: { color: "#fff", fontSize: 24, fontWeight: "800" },

  bottomNavbar: {
    position: "absolute",
    left: 0,
    right: 0,
    bottom: 0,
    height: Platform.OS === "android" ? 98 : 88,
    backgroundColor: "#FFFFFF",
    borderTopWidth: 1,
    borderTopColor: "#E2E8F0",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-around",
    paddingBottom: Platform.OS === "android" ? 30 : 22
  },
  bottomIconBtn: { alignItems: "center", width: 84 },
  bottomIcon: { fontSize: 18 },
  bottomLabel: { marginTop: 2, fontSize: 12, color: "#475569" },
  fabSpacer: { width: 76 },
  fab: {
    position: "absolute",
    bottom: Platform.OS === "android" ? 44 : 34,
    alignSelf: "center",
    width: 62,
    height: 62,
    borderRadius: 31,
    backgroundColor: "#2563EB",
    alignItems: "center",
    justifyContent: "center",
    shadowColor: "#1D4ED8",
    shadowOpacity: 0.3,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 5 },
    elevation: 6
  },
  fabText: { color: "#fff", fontSize: 32, lineHeight: 34, fontWeight: "700" },

  sheetOverlay: { flex: 1, justifyContent: "flex-end", backgroundColor: "rgba(15,23,42,0.35)" },
  sheet: {
    backgroundColor: "#fff",
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingHorizontal: 16,
    paddingTop: 10,
    paddingBottom: 24
  },
  sheetGrabber: {
    width: 44,
    height: 5,
    borderRadius: 99,
    backgroundColor: "#CBD5E1",
    alignSelf: "center",
    marginBottom: 10
  },
  sheetTitle: { fontSize: 20, fontWeight: "700", color: "#0F172A", marginBottom: 8 },
  continueBtn: {
    marginTop: 16,
    backgroundColor: "#2563EB",
    borderRadius: 10,
    paddingVertical: 13,
    alignItems: "center"
  },
  continueBtnText: { color: "#fff", fontWeight: "700", fontSize: 15 },

  modalBackdrop: { flex: 1, backgroundColor: "rgba(9,16,28,0.45)", justifyContent: "center", paddingHorizontal: 20 },
  modalCard: { backgroundColor: "#fff", borderRadius: 16, padding: 18, maxHeight: "70%" },
  modalTitle: { fontSize: 22, fontWeight: "700", color: "#0F172A", marginBottom: 6 },
  selectorInput: {
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 12,
    backgroundColor: "#fff"
  },
  selectorText: { color: "#0F172A", fontSize: 15 },
  selectorPlaceholder: { color: "#64748B", fontSize: 15 },
  optionItem: { paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: "#EEF2F8" },
  optionText: { color: "#0F172A", fontSize: 15 },
  modalCloseBtn: {
    marginTop: 12,
    borderWidth: 1,
    borderColor: "#CBD5E1",
    borderRadius: 10,
    paddingVertical: 10,
    alignItems: "center"
  },
  modalCloseText: { color: "#334155", fontWeight: "600" }
  ,
  scannerScreen: {
    flex: 1,
    backgroundColor: "#0B1220"
  },
  scannerHeader: {
    paddingHorizontal: 14,
    paddingVertical: 10,
    backgroundColor: "#fff",
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center"
  },
  scannerTitle: {
    fontSize: 18,
    fontWeight: "700",
    color: "#0F172A"
  },
  scannerBody: {
    flex: 1,
    padding: 12
  },
  cameraView: {
    flex: 1,
    borderRadius: 14,
    overflow: "hidden"
  },
  scannerFallback: {
    flex: 1,
    borderRadius: 14,
    backgroundColor: "#111827",
    alignItems: "center",
    justifyContent: "center"
  },
  scanGuide: {
    marginTop: 10,
    alignItems: "center"
  },
  scanGuideText: {
    color: "#E2E8F0",
    fontSize: 13
  }
});
