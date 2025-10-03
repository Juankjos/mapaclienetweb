#!/usr/bin/env python3
# -*- coding: utf-8 -*-
r"""
ingesta_json_a_sql_clientes.py

Script para ingerir datos desde varios archivos JSON a la base de datos MySQL "clientes"
(servida por XAMPP en localhost/phpMyAdmin). Inserta/actualiza registros en la tabla "reportes".

Requisitos previos (una vez):
    pip install mysql-connector-python

Suposiciones:
- La tabla `reportes` tiene PK en `IDReporte` (INT o BIGINT) para permitir ON DUPLICATE KEY UPDATE.
- Los archivos JSON existen en C:\xampp\htdocs\mapaclienetweb\mapa1\jsons
- Formato de fecha en GetCONCITAS.json: "dd/mm/yyyy hh:MM:ss a. m./p. m." (ej. "02/10/2025 12:00:00 a. m.")

Uso:
    python ingesta_json_a_sql_clientes.py
"""
from __future__ import annotations

import json
import os
import re
from datetime import datetime
from typing import Any, Dict, Optional

import mysql.connector
from mysql.connector import Error

# ------------------------- CONFIGURACIÓN ------------------------- #
DB_HOST = "127.0.0.1"
DB_PORT = 3306
DB_USER = "root"
DB_PASSWORD = ""       # Ajusta si tu root tiene contraseña
DB_NAME = "clientes"

# Carpeta con los JSON de origen
JSON_DIR = r"C:\xampp\htdocs\mapaclienetweb\mapa1\jsons"

# Nombres de archivo
F_BUSCA_DET_CITAS = "GetBUSCADetCitas.json"
F_BUSCLI_CONTRATO = "GetBUSCLIPORCONTRATO2.json"
F_DET_ORD_SER = "GetDame_DetOrdSer.json"
F_CONCITAS = "GetCONCITAS.json"


# ------------------------- UTILIDADES ------------------------- #
def read_json(path: str) -> Any:
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def first_not_none(*vals):
    """Devuelve el primer valor que no sea None/''/solo espacios, si no hay, ''."""
    for v in vals:
        if v is None:
            continue
        if isinstance(v, str) and v.strip() == "":
            continue
        return v
    return ""


def normalize_am_pm_es_to_en(fecha_str: str) -> str:
    """
    Convierte ' a. m.' / ' p. m.' (es) a 'AM' / 'PM' para que datetime.strptime lo entienda con %p.
    También tolera 'a.m.' / 'p.m.' sin espacios.
    """
    if fecha_str is None:
        return ""
    s = fecha_str.strip()

    # Variantes con puntos/espacios
    s = re.sub(r"\s*a\.?\s*m\.?\s*$", " AM", s, flags=re.IGNORECASE)
    s = re.sub(r"\s*p\.?\s*m\.?\s*$", " PM", s, flags=re.IGNORECASE)
    return s


def parse_fecha_sql(fecha_str: Optional[str]) -> Optional[str]:
    """
    Recibe '02/10/2025 12:00:00 a. m.' y devuelve '2025-10-02 00:00:00' (formato SQL).
    Acepta None y devuelve None.
    """
    if not fecha_str:
        return None
    s = normalize_am_pm_es_to_en(fecha_str)
    # Intentar dd/mm/yyyy 12h con AM/PM
    fmts = [
        "%d/%m/%Y %I:%M:%S %p",  # 02/10/2025 12:00:00 AM
        "%d/%m/%Y %H:%M:%S",     # 02/10/2025 00:00:00 (por si viene en 24h)
        "%d/%m/%Y %I:%M %p",     # 02/10/2025 12:00 AM (sin segundos)
    ]
    dt = None
    for fmt in fmts:
        try:
            dt = datetime.strptime(s, fmt)
            break
        except ValueError:
            continue
    if dt is None:
        raise ValueError(f"No se pudo parsear la fecha: '{fecha_str}'")
    return dt.strftime("%Y-%m-%d %H:%M:%S")


def build_direccion(cli: Dict[str, Any]) -> str:
    """
    Construye 'Calle NumExt Colonia Ciudad' tomando preferentemente
    campos sin guion bajo y usando los sufijos '_' como respaldo.
    """
    # Preferencias: Calle vs Calle_, Colonia vs Colonia_, Ciudad vs Ciudad_
    calle = first_not_none(cli.get("Calle"), cli.get("Calle_"))
    numext = first_not_none(cli.get("NumExt"))
    colonia = first_not_none(cli.get("Colonia"), cli.get("Colonia_"))
    ciudad = first_not_none(cli.get("Ciudad"), cli.get("Ciudad_"))

    partes = [str(calle).strip(), str(numext).strip(), str(colonia).strip(), str(ciudad).strip()]
    # Quitar vacíos y regresar unidos por un solo espacio
    return " ".join([p for p in partes if p and p != "None"]).strip()


def get_nested(d: Dict[str, Any], keys: list[str], default=None):
    for k in keys:
        if d is None:
            return default
        d = d.get(k)
    return d if d is not None else default


# ------------------------- INGESTA ------------------------- #
def ingest_reportes(conn):
    """
    Lee los cuatro JSON y hace un UPSERT en `reportes` con los campos:
    IDReporte (Clv_Cita), Contrato (IdContrato), Nombre (NomCompleto),
    Direccion (Calle NumExt Colonia Ciudad), Problema (Descripcion), FechaAgendada (Fecha)
    """
    # Cargar JSONs
    ruta1 = os.path.join(JSON_DIR, F_BUSCA_DET_CITAS)
    ruta2 = os.path.join(JSON_DIR, F_BUSCLI_CONTRATO)
    ruta3 = os.path.join(JSON_DIR, F_DET_ORD_SER)
    ruta4 = os.path.join(JSON_DIR, F_CONCITAS)

    data1 = read_json(ruta1)  # GetBUSCADetCitas.json
    data2 = read_json(ruta2)  # GetBUSCLIPORCONTRATO2.json
    data3 = read_json(ruta3)  # GetDame_DetOrdSer.json
    data4 = read_json(ruta4)  # GetCONCITAS.json

    # --- IDReporte ---
    det_citas = get_nested(data1, ["GetBUSCADetCitasResult"], {})
    # cubrir variantes 'Clv_Cita' y 'Clv_cita'
    id_reporte = first_not_none(
        det_citas.get("Clv_Cita"),
        det_citas.get("Clv_cita"),
        det_citas.get("ClvCita"),
    )
    if id_reporte in ("", None):
        # como respaldo, intentar del CONCITAS
        concitas = get_nested(data4, ["GetCONCITASResult"], {})
        id_reporte = first_not_none(concitas.get("Clv_Cita"), concitas.get("Clv_cita"))
    if id_reporte in ("", None):
        raise ValueError("No se encontró Clv_Cita/Clv_cita para IDReporte.")

    try:
        id_reporte = int(id_reporte)
    except Exception:
        raise ValueError(f"IDReporte no es numérico: {id_reporte!r}")

    # --- Contrato, Nombre, Direccion ---
    cli = get_nested(data2, ["GetBUSCLIPORCONTRATO2Result"], {})
    contrato = cli.get("IdContrato")
    nombre = cli.get("NomCompleto")
    direccion = build_direccion(cli)

    # --- Problema ---
    det_ord = get_nested(data3, ["GetDame_DetOrdSerResult"], [])
    problema = ""
    if isinstance(det_ord, list) and det_ord:
        problema = first_not_none(det_ord[0].get("Descripcion"))

    # --- FechaAgendada ---
    concitas = get_nested(data4, ["GetCONCITASResult"], {})
    fecha_raw = concitas.get("Fecha")
    fecha_agendada = parse_fecha_sql(fecha_raw) if fecha_raw else None

    # Preparar SQL con UPSERT
    sql = """
        INSERT INTO reportes (IDReporte, Contrato, Nombre, Direccion, Problema, FechaAgendada)
        VALUES (%s, %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
            Contrato = VALUES(Contrato),
            Nombre = VALUES(Nombre),
            Direccion = VALUES(Direccion),
            Problema = VALUES(Problema),
            FechaAgendada = VALUES(FechaAgendada);
    """

    vals = (
        id_reporte,
        contrato,
        nombre,
        direccion,
        problema,
        fecha_agendada,
    )

    with conn.cursor() as cur:
        cur.execute(sql, vals)
    conn.commit()

    print("✔ Inserción/actualización en `reportes` completada.")
    print(f"  IDReporte: {id_reporte}")
    print(f"  Contrato: {contrato}")
    print(f"  Nombre: {nombre}")
    print(f"  Dirección: {direccion}")
    print(f"  Problema: {problema}")
    print(f"  FechaAgendada: {fecha_agendada}")


def connect_db():
    return mysql.connector.connect(
        host=DB_HOST,
        port=DB_PORT,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME,
        autocommit=False,
    )


def main():
    print("== Ingesta JSON → MySQL (tabla reportes) ==")
    print(f"Directorio JSON: {JSON_DIR}")
    try:
        conn = connect_db()
    except Error as e:
        print("✖ Error al conectar a MySQL:", e)
        return

    try:
        ingest_reportes(conn)
    except Exception as e:
        conn.rollback()
        print("✖ Error durante la ingesta:", e)
    else:
        print("✔ Proceso finalizado correctamente.")
    finally:
        try:
            conn.close()
        except Exception:
            pass


if __name__ == "__main__":
    main()
