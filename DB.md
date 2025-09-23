# DB.md

## Általános irányelvek
- **Adatbázis**: PostgreSQL (12+)
- **GDPR alapelvek**:
  - *Adatminimalizálás*: csak a legszükségesebb adatokat tároljuk.
  - *Soft delete*: rekord törlés helyett `deleted_at` mező kitöltése.
  - *Anonimizálás*: PII törlése/maszkolása, `anonymized_at` mező rögzíti a dátumot.
- **Azonosítók**:
  - `person_id` → belső, BIGSERIAL, PRIMARY KEY (FK kapcsolatokhoz, gyors indexeléshez).
  - `person_uuid` → külső, UUID, minden frontend/API hivatkozás alapja.
- **Státuszok**: személy állapotát `status` oszlop tárolja (enum):
  - `active` – aktív tag
  - `inactive` – inaktív tag
  - `deceased` – elhunyt

---

## ENUM típusok

```sql
CREATE TYPE gender_t AS ENUM ('male','female','other','undisclosed');
CREATE TYPE person_status AS ENUM ('active','inactive','deceased');
```

---

## persons tábla

**Cél**: minden személy alapszintű nyilvántartása.

```sql
CREATE TABLE persons (
  person_id        BIGSERIAL PRIMARY KEY,
  person_uuid      UUID NOT NULL DEFAULT gen_random_uuid(),

  -- Név
  given_name       VARCHAR(100) NOT NULL,
  middle_name      VARCHAR(100),
  family_name      VARCHAR(100) NOT NULL,

  -- Automatikus teljes név
  display_name     TEXT GENERATED ALWAYS AS (
    btrim(
      given_name
      || ' '
      || COALESCE(NULLIF(middle_name,'' ) || ' ', '')
      || family_name
    )
  ) STORED,

  -- Opcionális adatok
  birth_date       DATE,
  national_id_cnp  VARCHAR(32),
  gender           gender_t NOT NULL DEFAULT 'undisclosed',

  -- Életciklus
  status           person_status NOT NULL DEFAULT 'active',
  date_of_death    DATE,

  -- Időbélyegek
  created_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
  deleted_at       TIMESTAMPTZ,
  anonymized_at    TIMESTAMPTZ,

  -- Egyediség és konzisztencia
  CONSTRAINT uq_persons_uuid UNIQUE (person_uuid),
  CONSTRAINT uq_persons_cnp UNIQUE (national_id_cnp),
  CONSTRAINT chk_death_consistency
    CHECK (status <> 'deceased' OR date_of_death IS NOT NULL)
);
```

---

## Indexek
- `uq_persons_uuid` → minden UUID egyedi.
- `uq_persons_cnp` → személyi szám opcionális, de ha meg van adva, legyen egyedi.
- `ix_persons_name` → névre kereséshez.
- `ix_persons_status` → státusz szerinti kereséshez.

---

## Trigger: `updated_at` automatikus frissítés

```sql
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at := now();
  RETURN NEW;
END; $$ LANGUAGE plpgsql;

CREATE TRIGGER trg_persons_updated_at
BEFORE UPDATE ON persons
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();
```

---

## Seed adatok

```sql
INSERT INTO persons (given_name, family_name, gender, birth_date, status, date_of_death)
VALUES 
  ('Ádám', 'Kovács', 'male',   '1985-04-12', 'active',   NULL),
  ('Éva',  'Szabó',  'female', '1990-07-23', 'inactive', NULL),
  ('János','Nagy',   'male',   '1940-01-05', 'deceased', '2020-02-10')
ON CONFLICT DO NOTHING;

-- Ellenőrzés
SELECT person_id, person_uuid, display_name, status FROM persons;
```

---

## Megállapodások
- **Frontend / API** mindig `person_uuid`-dal hivatkozik személyre.
- **`person_id`** csak belső használatra marad, FK-khoz.
- **Törlés**: `deleted_at` kitöltésével (rekord bent marad).
- **Anonimizálás**: `anonymized_at` kitöltése + érzékeny mezők törlése/maszkolása.
- **Következő lépés**: kapcsolódó táblák (contacts, addresses, consents) definiálása.
---


Adatbazis implementalas

Users table kezdetleges felepitese 23-09-2025
```sql
-- 1) Enable pgcrypto extension (required for crypt/gen_salt)
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- 2) Create users table
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    status SMALLINT NOT NULL DEFAULT 1,     -- 1 = active, 0 = disabled
    last_login_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 3) Insert test user
-- Password (plain): 'tengerimalac'
-- The crypt(...) call stores a bcrypt hash generated with gen_salt('bf', 12)
INSERT INTO users (email, password_hash, name, status, created_at, updated_at)
VALUES (
  'kovacszsoltsp@gmail.com',
  crypt('tengerimalac', gen_salt('bf', 12)),
  'Admin',
  1,
  NOW(),
  NOW()
);
```
Test if the enription is worked 
``` sql 
SELECT password_hash = crypt('tengerimalac', password_hash) AS match
FROM users WHERE email = 'kovacszsoltsp@gmail.com';
```