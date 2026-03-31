"""
Tests for file_handler.py file filtering functionality.

Tests the list_upload_files function with exclude_prepared parameter.
"""

import sys
import tempfile
from pathlib import Path

import pytest

# Add src directory to path
sys.path.insert(0, str(Path(__file__).parent.parent / "src"))

from file_handler import list_upload_files


@pytest.fixture
def temp_upload_dir():
    """Create a temporary directory with test TSV files."""
    with tempfile.TemporaryDirectory() as tmpdir:
        temp_path = Path(tmpdir)

        # Create test files
        (temp_path / "original1.tsv").write_text("test data 1")
        (temp_path / "original2.tsv").write_text("test data 2")
        (temp_path / "original1_prepared.tsv").write_text("prepared data 1")
        (temp_path / "original2_prepared.tsv").write_text("prepared data 2")
        (temp_path / "readme.txt").write_text("not a tsv file")

        yield temp_path


def test_list_upload_files_all(temp_upload_dir):
    """Test listing all TSV files without filtering."""
    files = list_upload_files(uploads_dir=str(temp_upload_dir), exclude_prepared=False)

    # Should find all 4 TSV files
    assert len(files) == 4

    file_names = [f.name for f in files]
    assert "original1.tsv" in file_names
    assert "original2.tsv" in file_names
    assert "original1_prepared.tsv" in file_names
    assert "original2_prepared.tsv" in file_names
    assert "readme.txt" not in file_names


def test_list_upload_files_exclude_prepared(temp_upload_dir):
    """Test listing files with prepared files excluded."""
    files = list_upload_files(uploads_dir=str(temp_upload_dir), exclude_prepared=True)

    # Should find only 2 unprepared TSV files
    assert len(files) == 2

    file_names = [f.name for f in files]
    assert "original1.tsv" in file_names
    assert "original2.tsv" in file_names
    assert "original1_prepared.tsv" not in file_names
    assert "original2_prepared.tsv" not in file_names


def test_list_upload_files_only_prepared(temp_upload_dir):
    """Test filtering to get only prepared files."""
    all_files = list_upload_files(uploads_dir=str(temp_upload_dir), exclude_prepared=False)
    prepared_files = [f for f in all_files if f.stem.endswith("_prepared")]

    # Should find only 2 prepared files
    assert len(prepared_files) == 2

    file_names = [f.name for f in prepared_files]
    assert "original1_prepared.tsv" in file_names
    assert "original2_prepared.tsv" in file_names
    assert "original1.tsv" not in file_names
    assert "original2.tsv" not in file_names


def test_list_upload_files_empty_directory():
    """Test listing files in an empty directory."""
    with tempfile.TemporaryDirectory() as tmpdir:
        files = list_upload_files(uploads_dir=tmpdir, exclude_prepared=False)
        assert len(files) == 0


def test_list_upload_files_creates_directory():
    """Test that the function creates the directory if it doesn't exist."""
    with tempfile.TemporaryDirectory() as tmpdir:
        non_existent_path = Path(tmpdir) / "uploads"
        assert not non_existent_path.exists()

        files = list_upload_files(uploads_dir=str(non_existent_path), exclude_prepared=False)

        # Directory should be created
        assert non_existent_path.exists()
        assert len(files) == 0


def test_list_upload_files_sorted(temp_upload_dir):
    """Test that files are returned in sorted order."""
    files = list_upload_files(uploads_dir=str(temp_upload_dir), exclude_prepared=False)

    file_names = [f.name for f in files]
    # Should be sorted alphabetically
    assert file_names == sorted(file_names)


def test_exclude_prepared_with_no_prepared_files():
    """Test exclude_prepared when there are no prepared files."""
    with tempfile.TemporaryDirectory() as tmpdir:
        temp_path = Path(tmpdir)
        (temp_path / "file1.tsv").write_text("data 1")
        (temp_path / "file2.tsv").write_text("data 2")

        files = list_upload_files(uploads_dir=tmpdir, exclude_prepared=True)

        # Should find both files since neither is prepared
        assert len(files) == 2


def test_exclude_prepared_with_only_prepared_files():
    """Test exclude_prepared when all files are prepared."""
    with tempfile.TemporaryDirectory() as tmpdir:
        temp_path = Path(tmpdir)
        (temp_path / "file1_prepared.tsv").write_text("data 1")
        (temp_path / "file2_prepared.tsv").write_text("data 2")

        files = list_upload_files(uploads_dir=tmpdir, exclude_prepared=True)

        # Should find no files since all are prepared
        assert len(files) == 0
